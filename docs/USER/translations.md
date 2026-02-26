---
description: Available translations for Health Checker for Joomla, and how to contribute your own.
---

# Translations

Health Checker for Joomla ships with English (en-GB) as the default language. Community-contributed translations are bundled directly into the extension package so they work out of the box.

## Available Languages

| Language | Locale | Contributor | Link |
|----------|--------|-------------|------|
| English | en-GB | Phil E. Taylor | Built-in |
| Spanish | es-ES | Andrés Restrepo | [alamarte.com](https://alamarte.com/descargas/traducciones/healthchecker) |

## How Joomla Loads Translations

Joomla automatically selects the correct language file based on the administrator's language preference. If a translation is missing for a particular string, Joomla falls back to the English (en-GB) version.

Each extension in the Health Checker package includes its own `language/` folder with subfolders per locale (e.g. `language/en-GB/`, `language/es-ES/`).

## Contributing a Translation

Want to translate Health Checker into your language? Here's how.

### Files to Translate

Each extension has one or two `.ini` files. The `.sys.ini` files contain strings used during installation and in the extension manager.

**Component** (`healthchecker/component/language/{locale}/`)
- `com_healthchecker.ini`
- `com_healthchecker.sys.ini`

**Module** (`healthchecker/module/language/{locale}/`)
- `mod_healthchecker.ini`
- `mod_healthchecker.sys.ini`

**Core Plugin** (`healthchecker/plugins/core/language/{locale}/`)
- `plg_healthchecker_core.ini` — this is the largest file, containing strings for all 130+ built-in checks
- `plg_healthchecker_core.sys.ini`

**Example Plugin** (`healthchecker/plugins/example/language/{locale}/`)
- `plg_healthchecker_example.ini`

**Akeeba Backup Plugin** (`healthchecker/plugins/akeebabackup/language/{locale}/`)
- `plg_healthchecker_akeebabackup.ini`
- `plg_healthchecker_akeebabackup.sys.ini`

**Akeeba Admin Tools Plugin** (`healthchecker/plugins/akeebaadmintools/language/{locale}/`)
- `plg_healthchecker_akeebaadmintools.ini`
- `plg_healthchecker_akeebaadmintools.sys.ini`

**mySites.guru Plugin** (`healthchecker/plugins/mysitesguru/language/{locale}/`)
- `plg_healthchecker_mysitesguru.ini`
- `plg_healthchecker_mysitesguru.sys.ini`

### Translation Rules

1. **Copy the `en-GB` files** as your starting point and rename them with your locale code (e.g. `fr-FR`, `de-DE`)
2. **Keep the language keys identical** — only translate the values (the part after the `=` sign)
3. **Use double quotes** around values that contain special characters
4. **Do not translate** placeholder tokens like `%s`, `%d`, or `%1$s` — these are replaced at runtime
5. **Preserve HTML** in values — some strings contain `<code>`, `<strong>`, or `<br>` tags

### File Format Example

```ini
; Keep the key exactly as-is, only translate the value
COM_HEALTHCHECKER="Health Checker"
COM_HEALTHCHECKER_REPORT_TITLE="Site Health Report"
```

### How to Submit

- **Pull request**: Fork the [GitHub repository](https://github.com/mySites-guru/HealthCheckerForJoomla), add your translation files under each extension's `language/{locale}/` folder, and open a PR
- **Email**: Send your translated files to [phil@phil-taylor.com](mailto:phil@phil-taylor.com)

Translators are credited in this documentation and in the project's release notes.
