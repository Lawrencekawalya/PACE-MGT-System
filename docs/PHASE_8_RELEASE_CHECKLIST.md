# Phase 8 Release Checklist

## Automated release evidence

- [ ] `composer ci:check` passes.
- [ ] `npm run build` completes.
- [ ] `composer audit` reports no known vulnerable production dependency.
- [ ] `npm audit --omit=dev` reports no known vulnerable production dependency.
- [ ] `php artisan system:check` passes against production dependencies.
- [ ] `php artisan system:validate-data` passes.
- [ ] `php artisan catalogue:reconcile` matches the approved workbook and recorded checksum.
- [ ] Backup creation and a restore drill pass on the production database engine.
- [ ] `/up` and `/ready` return HTTP 200 through HTTPS.

## Hosting and data approval

- [ ] Production database, mail, cache, queue, logs, scheduler, TLS, monitoring, and off-site backup destinations are configured.
- [ ] Exactly one academic year and term are active.
- [ ] Every student has a supervising teacher.
- [ ] Approved production catalogue counts match the reconciliation output.
- [ ] Opening PACE booklet and score-key quantities match signed physical counts.
- [ ] Retention periods have been approved by FICA.

## Client acceptance

- [ ] Administrator scenario passes.
- [ ] Teacher scenario passes.
- [ ] Storekeeper scenario passes.
- [ ] Desktop, tablet, mobile, keyboard, focus, labels, contrast, and dialog checks pass.
- [ ] No critical or high-severity defect remains open.
- [ ] Deployment rollback evidence is attached.
- [ ] Named FICA representatives approve the MVP release.

This checklist is intentionally not pre-approved by development. Production catalogue reconciliation, opening stock, infrastructure restore evidence, device testing, and client authorization require the actual release environment and named client representatives.
