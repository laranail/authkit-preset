# Installer

## Options

Use `--stack=blade` (the only stack), `--model='App\Models\User'`, and select optional flows
with `--api`, `--password-reset`, `--email-verification`, `--passkeys`,
`--two-factor-authentication` and `--bot-protection`. `--publish-routes` and `--publish-views`
create application-owned resources. Use `--no-interaction` for deterministic provisioning;
without explicit feature flags it keeps the non-interactive base features, not every optional flow.
The interactive installer offers feature selection. It never installs the optional social package.

Preview the actions selected by your installation options without writing files:

```sh
php artisan laranail::authkit-preset.install --stack=blade --api --passkeys --publish-routes --publish-views --dry-run --no-interaction
```

Repeat `--skip` to leave selected steps untouched. Other requested steps still run:

```sh
php artisan laranail::authkit-preset.install --stack=blade --api --passkeys --publish-routes --publish-views --skip=config --skip=environment --no-interaction
```

| Step | Controls |
| --- | --- |
| `model` | Authentication model edits |
| `config` | AuthKit/preset config publishing and selected feature updates |
| `tailwind` | Tailwind source registration |
| `passkeys` | Passkey JavaScript and package.json updates |
| `migrations` | All selected Sanctum, passkey and two-factor migrations |
| `routes` | Optional route publishing |
| `views` | Optional Blade view publishing |
| `environment` | .env and .env.example updates |

Unknown step names fail before any changes. Optional steps still require their enabling options;
`--skip` does not enable a step. Dry-run can be combined with `--skip` and `--force` and performs
no writes. On a normal run, `--force` retains its existing overwrite behavior and never overrides
a skipped step. When skipping prerequisites such as config, migrations, or model edits, provide
those changes in your application before using the selected features.

---

[← Docs index](../README.md#documentation)
