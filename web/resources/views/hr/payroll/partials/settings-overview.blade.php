<div class="tich-card tich-mb-8">
    <h2 class="tich-h3">Employee payroll columns</h2>
    <p class="tich-caption tich-mt-2">These match the payroll register: <strong>PAYE</strong>, <strong>NSSF</strong>, <strong>SHA/SHIF</strong>, and <strong>AHL</strong> (employer side excluded here).</p>
    <ol class="tich-mt-4" style="margin: 0; padding-left: 1.25rem; display: grid; gap: 0.5rem;">
        <li><strong>PAYE</strong> — progressive bands on <em>taxable income</em> (first tab below).</li>
        <li><strong>NSSF, SHA/SHIF, AHL</strong> — calculated automatically from Kenya rules (NSSF tiers, SHIF floor, AHL % of gross). Personal relief under Tax items.</li>
        <li><strong>Taxable pay</strong> = gross pay minus employee NSSF, SHA/SHIF, and AHL.</li>
        <li><strong>Net PAYE</strong> = PAYE before relief minus monthly personal relief (default KES 2,400).</li>
    </ol>
    <p class="tich-caption tich-mt-4">Statutory rates and tier caps are configured in <code>config/tich-payroll.php</code> (<code>kenya_statutory</code>). Adjust PAYE brackets on the bands tab when KRA updates them.</p>
</div>