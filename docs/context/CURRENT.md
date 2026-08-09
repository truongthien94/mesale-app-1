# Current Migration Context

- Latest checkpoint: `CKP-20260809-008`
- Session: `SES-20260809-002`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 auth contract implementation + M1 Expo foundation verification
- Code gate: received (`ĐỒNG Ý CODE`)
- Push gate: received; branch push completed
- Repository scope: standalone `C:\Users\ThichMMO\Desktop\cashback`
- Intended remote: `https://github.com/thichmmo/mesale-app`
- GitHub visibility: private
- GitHub default branch: `codex/migration-20260809` (automatically assigned after the first push)
- GitHub CLI: authenticated as `thichmmo`
- GitHub Issues: migration labels and Issues #1-#5 created
- GitHub branch: `codex/migration-20260809` pushed to origin
- GitHub PR: blocked because the previously empty repository has no distinct base branch
- README: full redacted project, development, security, API, milestone, and release guide added and checked
- GitHub Project: blocked because CLI token lacks `read:project` scope
- Current blocker: production Open API returns `503 API_DISABLED`
- Verification blocker: PHP CLI is unavailable in PATH (`BLK-TOOL-001`)
- Security status: no credentials or production member data included in Markdown
- Current tasks: `TSK-MOB-001` (M0 auth/API) and `TSK-MOB-002` (foundation review)

## Next Action

Create the approved empty `main` base and open the Pull Request, then continue Laravel contract tests, Apple/Google/staging API work, and representative device authentication verification. Do not merge automatically.
