# Request lifetime: placement proposal, not an implemented guarantee

## Recommendation

Keep application-specific freshness deadlines, retry choices, reconciliation,
alerting, and permission decisions in the consuming application. Do not merge a
large HTTP lifecycle rewrite merely to support one consumer. The reusable gap is
real, but a separate opt-in request-lifetime contract needs agreement and tests
before implementation. This proposal changes no runtime behavior.

## Evidence at master 8fcbaca9e229dd0ff37a1eaaaa7e4836f22779f4

- `HttpTrait::queueRequest()` creates a Deferred with no canceller (line 156).
  Its promise is separate from the Deferred used by queue dispatch (line 344)
  and execution (line 226). Cancelling the public promise does not explicitly
  remove the Request or cancel the active driver's promise.
- `executeRequest()` directly invokes itself on 502/525 (line 295), retaining
  the same Request and execution Deferred. There is no attempt counter or delay;
  contrary to its comment, this branch does not enqueue at the bucket tail.
- Bucket dispatch and global dispatch own different queues. Bucket-specific 429
  waits re-enqueue the Request and create an untracked retry timer; global and
  bucket reset timers affect other requests too (`Bucket.php`, lines 197–202).
- React returns the Browser promise directly. HttpTrait neither retains that
  promise for cancellation nor connects it to cancellation of the public result.
  The Guzzle bridge has its own Deferred without cancellation wiring and can
  call blocking `wait()`. A client deadline cannot preempt blocking PHP code.
- PR #26 is an existing, separate promise-v2/v3 transition draft, not this fix.
  Do not duplicate it or change its owner/branch. Its description includes older
  rate-limit compatibility concerns; current master already makes RateLimit a
  RuntimeException, so do not assume that historical concern remains unfixed.

## Alternatives and tradeoffs

| Placement | Can provide | Cannot honestly promise |
| --- | --- | --- |
| Application result wrapper | Stop awaiting at an application deadline; ignore obsolete results; prevent stale business writes | Removal from HTTP queues, transport termination, stopping library-owned recursive retries |
| Application driver decorator | Count transport attempts per Request identity; reject before dispatch when its recorded budget expires; best-effort cancellation of its active underlying promise | Measuring queue residence before first driver call without an admission hook; physically removing bucket/global queue entries; stopping blocking drivers |
| Custom Http subclass/adapter | Explicit admission scope, application deadline and a controlled driver, with no new upstream API | Robustness if it overrides/copies private scheduling details; portability across upstream changes |
| Generic upstream request context | One opt-in budget across bucket wait, global wait, 429 wait and retries; removal/suppression before transport; best-effort transport cancellation | Remote rollback, exactly-once writes, business freshness or authorization, preemption of a blocking loop |

Start with the application wrapper for business correctness. If resource leakage
is material, a controlled driver decorator can reject repeated attempts and stale
work before transport, but must be described as a mitigation rather than full
queue cancellation. Associate metadata without putting it in HTTP headers and
clean it up on settlement to avoid retaining Request objects indefinitely. The
same Request identity is reused by the current retry path, but relying on this
as a public contract needs documentation or an admission API.

### Existing injection route

`Http::__construct($token, $loop, $logger, ?DriverInterface $driver = null)` already
accepts a driver, and `HttpTrait::setDriver(DriverInterface $driver)` replaces it.
`getDriver()` returns the currently configured driver for composition. A consumer
can therefore construct its own decorator implementing
`DriverInterface::runRequest(Request $request): PromiseInterface`, retain the
real driver inside it, and inject that decorator before issuing requests. No
upstream method addition is required for this mitigation. Confirm the consuming
client's own construction/injection path separately; this proposal does not claim
that every higher-level client exposes its Http instance in the same way.

This decorator can increment an identity-keyed counter before delegating. On a
subsequent library-owned 502/525 attempt it can reject rather than call the real
driver, stopping that retry chain. Its failure must be an Exception compatible
with the existing transport rejection callback. It must distinguish observed
502/525 retries from 429 scheduling if their budgets differ; blindly counting
every driver call changes 429 policy. Counting attempts alone does not stop a
single indefinitely pending transport. A decorator can add its own active-attempt
timer and cancel an underlying cancellable promise, but must settle its returned
promise and clean up handlers/timers safely, including late responses.

A time budget initialized at first `runRequest()` is a **dispatch budget**, not
an admission-to-completion deadline. Queue time has already elapsed by then.
Metadata must be scoped to each logical Request, not only endpoint/method, or
concurrent calls can accidentally share counters. A decorator that retains state
through 429 waits must account for that lifetime and dispose it eventually.
Never replace the shared driver while unrelated work is active merely to emulate
per-request policy; install a stable decorator at construction and scope its rules.

Retrying a write after a lost/ambiguous response may duplicate side effects.
Neither a timeout nor cancellation proves that Discord did not apply a request.
The application must decide which operations can be replayed/reconciled.

## Candidate upstream contract (not implemented)

An explicit request handle/context, rather than changing every HttpInterface
implementation, should carry optional `timeoutSeconds` and `maxGatewayRetries`.
These names are provisional. Absence preserves existing behavior. A retry budget
of zero means the first 502/525 is returned as failure; N means at most N extra
502/525 attempts, not N total attempts. Discord 429 handling must still respect
Retry-After and share the total deadline; do not treat 429 as an ordinary gateway
retry. A separate explicit handle cancellation avoids ambiguity about cancelling
one branch of a shared promise under promise v2 versus v3.

The timeout starts at admission, uses a monotonic clock, and spans every queue,
wait and transport attempt. Negative/non-finite values are rejected; zero must
have a documented meaning (recommend immediate expiration, not infinite).
Do not reset the budget during retries. Existing request methods remain unchanged;
choosing where the opt-in method belongs is an API review decision, not settled by
this proposal. A timeout callback is cooperative and can run late if the loop is
blocked; check expiration again immediately before each dispatch/retry.

Required internal invariants:

1. Exactly one terminal state, with typed cancellation/deadline/retry-exhaustion
   failures distinguishable from HTTP and transport failures.
2. Suppress terminal requests in every queue and retry callback; remove queued
   entries promptly rather than retaining large request bodies until a long
   shared rate-limit timer expires.
3. Release global concurrency slots exactly once; late transport completions
   cannot re-release slots or resolve an already terminal result.
4. Keep shared rate-limit reset timers alive for other work. Request-local timers
   and references are cleaned at settlement; cancelling one request must not
   bypass rate limits for another.
5. Retain and cancel active transport promises only when cancellation is
   supported. State clearly that cancellation is local/best effort, never rollback.
   A non-cooperative driver may still be running after local settlement; define
   whether its slot is held until completion rather than silently exceeding the
   physical concurrency cap.
6. Handle synchronous driver exceptions and synchronously settled promises without
   re-entrancy leaks; never implement unbounded synchronous recursion.
7. Cover promise v2 and v3 explicitly without changing the independent #26 draft.

This spans Request, Bucket and HttpTrait scheduling, not just the React driver.
Shipping only a Deferred canceller would leave global queue/retry races open and
would advertise stronger behavior than the implementation can support.

## Verification plan

`tests/Discord/RequestLifecycleCharacterizationTest.php` is an inert baseline:
manual Deferred responses, no real driver, no HTTP server, no credentials, no
loop run. It documents current queued/active cancellation and immediate gateway
retry behavior; passing it does **not** mean cancellation is implemented. A later
fix should replace the expected historical behavior with its agreed contract.

Run this file directly (not `composer test`, which starts a server):

```sh
vendor/bin/phpunit tests/Discord/RequestLifecycleCharacterizationTest.php
```

Required acceptance matrix before a runtime PR: cancellation and deadline while
in bucket/global queues, during 429 timers, during active transport, immediately
before/after completion, and between 502/525 attempts; repeated cancel; shared
promise branches; non-cancellable and blocking-driver limitations; exactly-once
slot release; late completions; timeout/retry bounds; defaults unchanged; no
cross-request rate-limit damage. Use an injected fake clock/loop and manual
fake driver, never live Discord calls. Test supported PHP/dependency combinations,
including both promise majors (v3 requires an appropriately supported PHP version).

The dedicated `request-lifecycle-characterization.yml` workflow runs only this
inert file on PHP 8.4, separately resolving React Promise 2 and 3. PHP 8.4 exercises
both promise majors on one supported runtime; this is not a minimum-PHP or full
library compatibility matrix. The two existing documentation/version workflows
do not run these tests. Dependency/setup downloads require network access, but
the test itself uses no live HTTP or local HTTP server. Composer scripts/plugins
are disabled, no application secrets are supplied, and the workflow has read-only
repository permissions and a finite job timeout.

CI generates a separate `composer.lifecycle.json` from the root manifest's
unchanged runtime `require` and `autoload`, adding only PHPUnit as a development
dependency. The selected promise-major constraint is applied within the root's
supported range. This exercises the actual checked-out library source, but does
**not** validate the normal full-repository development installation. The normal
`composer.json` is not edited. This isolated job excludes PsySH and unrelated
development tools; it does not ignore advisories or disable Composer's security
policy. The existing `psy/psysh ^0.10.6` requirement remains unresolved in the
normal development manifest: advisory `PKSA-4s4z-t146-6123` blocked the original
full-development dependency resolution before any characterization tests ran.

These are known-gap assertions: when a future lifecycle fix adopts a different
contract, update them to assert that new contract rather than preserving the gap
to keep CI green. Runtime changes remain deferred pending placement/API review
and executable acceptance tests.
