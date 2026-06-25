# Release Process

Surqlize releases are published from signed Git tags. The Packagist publish workflow refuses unsigned, lightweight, or GitHub-unverified release tags.

## Prerequisites

- Add your GPG, SSH, or S/MIME signing key to your GitHub account.
- Configure Git to sign tags locally.
- Add these repository secrets in GitHub:
  - `PACKAGIST_USERNAME`
  - `PACKAGIST_TOKEN`

## Create A Signed Release

1. Make sure the release commit is on `main` and CI is green.
2. Create an annotated signed tag:

   ```bash
   git tag -s v0.1.0 -m "v0.1.0"
   ```

3. Push the signed tag:

   ```bash
   git push origin v0.1.0
   ```

4. Create a GitHub Release from that exact tag.

When the GitHub Release is published, `.github/workflows/publish-packagist.yml` validates the tag signature with GitHub's verification API before notifying Packagist.

## Verify Locally

Use this command before publishing if you want a local check:

```bash
git tag -v v0.1.0
```
