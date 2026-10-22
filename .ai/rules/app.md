---
paths:
  - 'app/FarmAssistant.php'
  - 'app/FarmReport*.php'
---

# App

## Keep assistant tools separate from farm calculations
The configured AI provider may call the allow-listed read_farm_report tool or answer a general knowledge question. Validate every tool argument before running fixed read-only queries. Never execute model-generated SQL, expose database credentials, or allow writes. With the user's authorization, send only the selected report result to the provider so it can compose the answer; cap detailed rows and chart points. Text filters may only search locally within the selected dataset. Current schema records one farm-wide flock, not houses or health-case counts. Financial totals use expenses once, including linked feed deliveries and worker advances. Herd-based ratios use farm_settings.total_herd_count and disclose missing recorded days.
