<!-- Inline styles for PDF generation -->
<style>
body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5; margin: 0; padding: 0; }
.tich-doc-sheet { max-width: 800px; margin: 0 auto; padding: 15pt; }
.tich-doc-letterhead { text-align: center; border-bottom: 3pt solid #1e40af; padding-bottom: 20pt; margin-bottom: 30pt; }
.tich-doc-letterhead__logo { max-height: 52pt; max-width: 140pt; object-fit: contain; display: block; margin: 0 auto 10pt; }
.tich-doc-letterhead__mark { display: inline-flex; align-items: center; justify-content: center; width: 42pt; height: 42pt; border-radius: 50%; background: #1e40af; color: #fff; font-weight: bold; font-size: 16pt; margin: 0 auto 10pt; }
.tich-doc-letterhead__name { margin: 0; font-size: 20pt; font-weight: bold; color: #1e40af; }
.tich-doc-letterhead__tagline { margin: 4pt 0 0; font-size: 10pt; color: #374151; }
.tich-doc-title-block { text-align: center; margin-bottom: 30pt; }
.tich-doc-title-block h1 { margin: 0 0 8pt; font-size: 24pt; color: #1e40af; font-weight: bold; }
.tich-doc-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12pt 24pt; margin-bottom: 25pt; font-size: 10pt; border: 1pt solid #cbd5e1; padding: 10pt 14pt; background: #f8fafc; }
.tich-doc-meta dt { font-weight: bold; color: #1e3a5f; margin-bottom: 3pt; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5pt; }
.tich-doc-meta dd { margin: 0 0 8pt; font-size: 10pt; color: #111827; }
.tich-doc-table { width: 100%; border-collapse: collapse; margin-top: 12pt; }
.tich-doc-table th, .tich-doc-table td { border: 1pt solid #000; padding: 8pt; text-align: left; }
.tich-doc-table th { background: #eef2f7; color: #1e3a5f; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.5pt; font-family: system-ui, sans-serif; }
.tich-doc-table .num { text-align: right; }
.tich-doc-table tfoot td { font-weight: bold; background: #f8fafc; }
.tich-doc-head { width: 100%; border-collapse: collapse; margin-bottom: 14pt; font-size: 10pt; }
.tich-doc-head th, .tich-doc-head td { border: 1pt solid #000; padding: 6pt 8pt; vertical-align: top; text-align: left; }
.tich-doc-head th { width: 20%; background: #f1f5f9; color: #1e3a5f; font-size: 8pt; font-weight: bold; letter-spacing: 0.4pt; text-transform: uppercase; white-space: nowrap; }
.tich-doc-head td { width: 30%; }
.tich-doc-head .num { font-weight: bold; }
.tich-doc-page-break { page-break-before: always; }
.tich-doc-section { margin-top: 12pt; }
.tich-doc-section h2 { margin: 0 0 6pt; font-size: 12pt; color: #1e3a5f; border-bottom: 1pt solid #cbd5e1; padding-bottom: 4pt; }
.tich-doc-session { margin-bottom: 8pt; }
.tich-doc-session strong { display: block; margin-bottom: 4pt; }
.tich-doc-timetable-grid table { width: 100%; border-collapse: collapse; }
.tich-doc-timetable-grid th, .tich-doc-timetable-grid td { border: 1pt solid #000; padding: 6pt; text-align: center; }
.tich-doc-timetable-grid th { background: #f0f4f8; font-weight: bold; }
.tich-doc-timetable-grid__time { width: 80pt; }
.tich-doc-timetable-grid__break td { background: #f3f4f6; }
.tich-payslip { font-family: 'Times New Roman', Times, serif; font-size: 10pt; line-height: 1.4; }
.tich-payslip__employee { display: flex; gap: 20pt; margin-bottom: 12pt; }
.tich-payslip__employee > div { flex: 1; }
.tich-payslip__label { display: block; font-size: 9pt; color: #6b7280; }
.tich-payslip__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12pt; margin-bottom: 12pt; }
.tich-payslip__panel { border: 1pt solid #d1d5db; border-radius: 4pt; padding: 8pt; }
.tich-payslip__panel-title { font-size: 11pt; font-weight: bold; margin: 0 0 6pt; color: #1e40af; }
.tich-payslip__table { width: 100%; border-collapse: collapse; font-size: 10pt; }
.tich-payslip__table th, .tich-payslip__table td { border: 1pt solid #d1d5db; padding: 4pt 6pt; text-align: left; }
.tich-payslip__table th { background: #f0f4f8; font-weight: bold; }
.tich-payslip__table .num { text-align: right; font-variant-numeric: tabular-nums; }
.tich-payslip__total-row { background: #f9fafb; font-weight: bold; }
.tich-payslip__net { display: flex; justify-content: space-between; align-items: center; padding: 8pt 12pt; background: #1e40af; color: #fff; font-size: 12pt; margin: 12pt 0; border-radius: 4pt; }
.tich-payslip__net span { font-weight: normal; }
.tich-payslip__detail { margin-top: 12pt; }
.tich-payslip__detail-title { font-size: 11pt; font-weight: bold; margin: 0 0 6pt; color: #1e40af; }
.tich-payslip__footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8pt; margin-top: 12pt; }
.tich-payslip__stat { border: 1pt solid #d1d5db; border-radius: 4pt; padding: 6pt 8pt; }
.tich-payslip__stat--wide { grid-column: 1 / -1; }
.tich-payslip__note { font-size: 8pt; color: #9ca3af; margin-top: 12pt; font-style: italic; }
.tich-doc--transcript .tich-doc-letterhead { border-bottom: 2.5pt solid #1e40af; padding-bottom: 10pt; margin-bottom: 14pt; }
.tich-doc--transcript .tich-doc-letterhead__logo { max-height: 62pt; max-width: 150pt; }
.tich-doc--transcript .tich-doc-letterhead__name { font-size: 16pt; }
.tich-doc--transcript .tich-doc-title-block { margin-bottom: 16pt; padding: 6pt 0; border-top: 0.6pt solid #cbd5e1; border-bottom: 0.6pt solid #cbd5e1; background: #f8fafc; }
.tich-doc--transcript .tich-doc-title-block h1 { font-size: 15pt; letter-spacing: 1.5pt; margin-bottom: 4pt; }
.tich-doc--transcript .tich-doc-title-block p { margin: 2pt 0 0; font-size: 9pt; color: #374151; }
.tich-transcript-panel { margin-top: 10pt; break-inside: avoid; page-break-inside: avoid; }
.tich-transcript-panel__title { margin: 0 0 6pt; padding-bottom: 3pt; border-bottom: 1.5pt solid #1e40af; font-family: system-ui, sans-serif; font-size: 9pt; font-weight: bold; letter-spacing: 1pt; color: #1e40af; }
.tich-transcript-identity { width: 100%; border-collapse: collapse; font-size: 9pt; }
.tich-transcript-identity th { width: 17%; border: 0.6pt solid #94a3b8; background: #f1f5f9; color: #1e3a5f; font-family: system-ui, sans-serif; font-size: 7pt; font-weight: bold; letter-spacing: 0.5pt; padding: 3pt 5pt; text-align: left; vertical-align: top; }
.tich-transcript-identity td { width: 33%; border: 0.6pt solid #94a3b8; padding: 3pt 5pt; vertical-align: top; }
.tich-transcript-identity td.gpa { font-weight: bold; color: #1e40af; }
.tich-transcript-results { font-size: 8.5pt; margin: 0 0 10pt; table-layout: fixed; width: 100%; }
.tich-transcript-results th, .tich-transcript-results td { padding: 3pt 2pt; }
.tich-transcript-results th { font-size: 6.5pt; letter-spacing: 0.2pt; white-space: nowrap; }
.tich-transcript-term { margin: 0 0 0; padding: 3pt 6pt; background: #1e40af; color: #ffffff; font-family: system-ui, sans-serif; font-size: 8pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; }
.tich-transcript-results .col-code { width: 11%; }
.tich-transcript-results .col-unit { width: 30%; }
.tich-transcript-results .col-credits { width: 8%; }
.tich-transcript-results .col-mark { width: 11%; }
.tich-transcript-results .col-grade { width: 8%; }
.tich-transcript-results .grade { text-align: center; font-weight: bold; color: #1e40af; }
.tich-transcript-results tfoot td { background: #eef2f7; white-space: nowrap; }
.tich-transcript-scale { width: 62%; font-size: 8.5pt; }
.tich-transcript-scale th, .tich-transcript-scale td { padding: 3pt 4pt; }
.tich-transcript-scale .col-grade { width: 22%; }
.tich-transcript-scale .grade { text-align: center; font-weight: bold; color: #1e40af; }
.tich-transcript-note { font-size: 7.5pt; color: #4b5563; margin-top: 4pt; }
.tich-transcript-cert { font-size: 8pt; color: #1f2937; border: 0.6pt solid #94a3b8; background: #f8fafc; padding: 6pt 8pt; margin-top: 12pt; }
</style>