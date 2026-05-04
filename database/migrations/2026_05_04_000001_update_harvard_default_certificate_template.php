<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $htmlTemplate = <<<'HTML'
<div class="certificate-container">
    <div class="header">
        <img src="{{logo_institucion}}" class="main-logo" alt="Logo institucional">
        <h1 class="institution">Certificate of Achievement</h1>
    </div>

    <div class="content">
        <p class="congratulations">This certifies that</p>
        <h2 class="student-name">{{nombre}}</h2>
        <p class="completion-text">
            has successfully completed the certification <strong>{{competencia}}</strong>,
            with result <strong>{{nota}}</strong>.
        </p>
        <p class="issue-date">Issued on {{fecha}}</p>
    </div>

    <div class="footer">
        <div class="signature-box">
            <img src="{{firma_director}}" class="signature-img" alt="Authorized signature">
            <div class="signature-line"></div>
            <p>{{firma_director_nombre}}</p>
        </div>

        <div class="verification-box">
            <img src="{{verificacion_qr}}" class="qr-code" alt="Verification QR">
            <p class="serial">Serial: {{serial}}</p>
            <p class="serial">Hash: {{integridad_hash}}</p>
        </div>
    </div>
</div>
HTML;

        $cssTemplate = <<<'CSS'
@page {
    size: letter;
    margin: 18px;
}

html,
body {
    margin: 0;
    padding: 0;
    font-family: serif;
    color: #1c1c1c;
}

.certificate-container {
    width: 100%;
    min-height: 100%;
    box-sizing: border-box;
    padding: 24px 28px 20px;
    border: 8px double #a51c30;
    background: #fff;
    text-align: center;
    page-break-inside: avoid;
}

.institution {
    margin: 10px 0 22px;
    font-size: 22pt;
    color: #a51c30;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.main-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 6px;
}

.congratulations {
    font-size: 13pt;
    font-style: italic;
    margin-top: 6px;
}

.student-name {
    font-size: 30pt;
    margin: 12px 0 12px;
    padding: 0 30px 8px;
    border-bottom: 1px solid #bcbcbc;
    display: inline-block;
}

.completion-text {
    font-size: 11.5pt;
    line-height: 1.45;
    margin: 8px 34px;
}

.issue-date {
    margin-top: 10px;
    font-size: 10pt;
    color: #555;
}

.footer {
    margin-top: 32px;
    width: 100%;
    display: table;
    table-layout: fixed;
}

.signature-box,
.verification-box {
    display: table-cell;
    width: 50%;
    vertical-align: bottom;
}

.signature-img {
    width: 150px;
    max-height: 70px;
    object-fit: contain;
    margin-bottom: 6px;
}

.signature-line {
    border-top: 1px solid #000;
    width: 190px;
    margin: 0 auto 6px;
}

.qr-code {
    width: 76px;
    height: 76px;
}

.serial {
    margin: 4px 0 0;
    font-size: 8pt;
    color: #666;
    word-break: break-all;
}
CSS;

        DB::table('certificate_templates')
            ->whereNull('certification_id')
            ->where('slug', 'harvard-style-default')
            ->update([
                'name' => 'Harvard Style Default',
                'html_template' => $htmlTemplate,
                'css_template' => $cssTemplate,
                'is_default' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentional no-op.
    }
};
