<!-- Inline styles for PDF generation -->
<style>
body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5; margin: 0; padding: 0; }
.tich-doc-sheet { max-width: 800px; margin: 0 auto; padding: 15pt; }
.tich-doc-letterhead { text-align: center; border-bottom: 3pt solid #1e40af; padding-bottom: 20pt; margin-bottom: 30pt; }
.tich-doc-letterhead__name { margin: 0; font-size: 20pt; font-weight: bold; color: #1e40af; }
.tich-doc-letterhead__tagline { margin: 4pt 0 0; font-size: 10pt; color: #374151; }
.tich-doc-title-block { text-align: center; margin-bottom: 30pt; }
.tich-doc-title-block h1 { margin: 0 0 8pt; font-size: 24pt; color: #1e40af; font-weight: bold; }
.tich-doc-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12pt; margin-bottom: 25pt; }
.tich-doc-meta dt { font-weight: bold; color: #6b7280; margin-bottom: 3pt; }
.tich-doc-meta dd { margin: 0; }
.tich-doc-table { width: 100%; border-collapse: collapse; margin-top: 12pt; }
.tich-doc-table th, .tich-doc-table td { border: 1pt solid #000; padding: 8pt; text-align: left; }
.tich-doc-table th { background: #f0f4f8; font-weight: bold; }
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
</style>