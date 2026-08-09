# Current Migration Context

- Latest checkpoint: `CKP-20260809-004`
- Session: `SES-20260809-002`
- Plan: [migration-plan.md](../migration-plan.md)
- Phase: M0 API audit + M1 Expo foundation verification
- Code gate: received (`ĐỒNG Ý CODE`)
- Push gate: not received (`ĐỒNG Ý PUSH GITHUB`)
- Repository scope: standalone `C:\Users\ThichMMO\Desktop\cashback`
- Intended remote: `https://github.com/thichmmo/mesale-app`
- GitHub visibility: private
- GitHub default branch: remote repository currently has no commit/default branch
- GitHub CLI: authenticated as `thichmmo`
- GitHub Issues: labels and initial M0/blocker issues created; no PR or push
- GitHub Project: blocked because CLI token lacks `read:project` scope
- Current blocker: production Open API returns `503 API_DISABLED`
- Verification blocker: PHP CLI is unavailable in PATH (`BLK-TOOL-001`)
- Security status: no credentials or production member data included in Markdown
- Current task: `TSK-MOB-002` (foundation review)

## Next Action

Complete M0 API contract work and run representative device/authentication verification for the M1 foundation. Do not push or merge.
