# Architecture: statspersonalinfos

## Purpose

A PrestaShop statistics module that aggregates anonymised customer demographic data (gender, age ranges, country distribution) to help merchants understand their customer base.

## Directory Structure

```
statspersonalinfos.php   - Module class; all business logic and rendering
upgrade/                 - Migration scripts
tests/                   - PHPUnit test stubs and PHPStan bootstrap
translations/            - Locale string overrides
```

## Key Design Decisions

- **Aggregated only**: Presents percentages and counts — no personally identifiable information is surfaced in the UI.
- **ModuleGraph subclass**: Renders demographic breakdowns as pie charts.

## Extension Points

- Add additional demographic breakdowns (e.g., registration method) in `getData()`.

## Dependency Flow

```
statspersonalinfos (ModuleGraph)
  └─> hookDisplayAdminStatsModules() — renders demographic charts
  └─> getData()                      — demographic aggregate queries
        └─> Db::getInstance()
```
