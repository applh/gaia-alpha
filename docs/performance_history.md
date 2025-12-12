# Performance Evolution History

This document tracks the performance benchmarks of Gaia Alpha over time to ensure no regressions are introduced.

## Benchmark Methodology
Benchmarks are run using `php cli.php bench:all` on a standard development environment.

| Date | Version | Boot Time | Router (req/sec) | DB (qps) | Template (ops) | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **2025-12-12** | **v0.24.0** | **56.97 ms** | **8,147** | **868,026** | **80,249** | Initial tracking entry. |
