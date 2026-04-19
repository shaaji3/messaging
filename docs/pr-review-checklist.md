# Draft PR Review Checklist (Utopia Messaging)

## 1) Product Principles Gate (must pass all)
- [ ] **Fast by default**: no additional work in the common send path unless a feature is explicitly enabled.
- [ ] **Easy to use**: existing examples/API still work with no changes.
- [ ] **No new required dependencies**: `composer` requirements unchanged (or strictly optional).

## 2) Backward Compatibility (BC)
- [ ] Public constructors and method signatures remain compatible.
- [ ] Existing response keys are preserved (`deliveredTo`, `type`, `results`).
- [ ] New fields (if any) are additive/optional, not breaking.
- [ ] Existing adapters still behave the same when feature flags are off.

## 3) Performance / Latency
- [ ] No extra JSON encode/decode cycles in hot paths.
- [ ] No per-message reflection or dynamic class scans.
- [ ] No unnecessary object allocations in recipient loops.
- [ ] Retry/failover logic short-circuits on success.
- [ ] Multi-send logic does not duplicate headers or payload transformations.

## 4) Simplicity of API
- [ ] New feature can be adopted in ≤10 lines of user code.
- [ ] Naming is explicit and unsurprising.
- [ ] No framework-like config DSL introduced.
- [ ] Defaults are safe and intuitive.

## 5) Dependency Discipline
- [ ] `composer.json` has no new required package for core behavior.
- [ ] Any helper package added is optional and justified.
- [ ] No hidden runtime service/container assumptions.

## 6) Reliability Semantics
- [ ] Failure modes are deterministic and documented.
- [ ] Errors clearly distinguish transport failure vs provider rejection.
- [ ] Retryability (if present) is explicit (`retryable: true/false`).
- [ ] Fallback order is deterministic and test-covered.

## 7) Test Quality
- [ ] Unit tests include happy path + failure path + edge cases.
- [ ] Tests verify unchanged behavior for old API usage.
- [ ] Tests cover partial success scenarios (multiple recipients).
- [ ] Tests avoid real network calls (mocked/stubbed adapter HTTP layer).
- [ ] No flaky timing-dependent tests.

## 8) Documentation / DX
- [ ] README includes one minimal usage snippet for new features.
- [ ] Docs state defaults, limits, and expected response shape.
- [ ] Migration note added if behavior might surprise existing users.
- [ ] Adapter contribution docs updated if extension points changed.

## 9) Security / Privacy
- [ ] No secrets logged in errors (API keys, tokens, auth headers).
- [ ] User identifiers/recipients are handled carefully in logs.
- [ ] URL/host validation remains strict for webhook adapters.

## 10) Merge Readiness
- [ ] Changelog entry added if workflow uses one.
- [ ] Clear PR title + concise why/what/how description.
- [ ] Follow-up tasks captured (not hidden in comments).
- [ ] Maintainer can explain the feature in one sentence.
