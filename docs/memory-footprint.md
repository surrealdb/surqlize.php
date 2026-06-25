# Memory Footprint Reports

Surqlize ships a small memory report command for repeatable, machine-readable checks:

```bash
vendor/bin/surqlize memory:footprint
vendor/bin/surqlize memory:footprint --iterations=5000 --output=memory-report.json
```

If the Composer bin proxy is not installed in the current checkout, run the same command through the package script directly:

```bash
php bin/surqlize memory:footprint
php bin/surqlize memory:footprint --iterations=5000 --output=memory-report.json
```

The report is JSON so agents and CI jobs can compare it against a previous run. Each scenario includes:

- `usage_delta_bytes`: retained PHP heap change after the scenario and GC.
- `real_usage_delta_bytes`: retained allocated memory change after the scenario and GC.
- `peak_delta_bytes`: peak PHP heap increase during the scenario.
- `peak_real_delta_bytes`: peak allocated memory increase during the scenario.
- `duration_ms`: elapsed wall-clock time for the scenario.

Useful comparison targets:

- `peak_delta_bytes` catches temporary allocation spikes.
- `usage_delta_bytes` catches retained memory growth.
- `real_usage_delta_bytes` is noisier, but can show allocator-level changes.

The default scenarios cover metadata reflection, field-set resolution, typed query compilation, generated field adapter compilation, graph traversal compilation, edge endpoint queries, single-row hydration, bulk hydration, field generation, and record-id filters. If a future report grows unexpectedly, start by comparing the scenario with the largest `duration_ms`, `peak_delta_bytes`, or retained `usage_delta_bytes` against the previous JSON file.
