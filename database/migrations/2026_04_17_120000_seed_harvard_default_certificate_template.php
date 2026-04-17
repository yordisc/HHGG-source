<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('certificate_templates')
            ->whereNull('certification_id')
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $now = now();

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
            <p>Director of Certification</p>
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
    margin: 24px;
}

body {
    font-family: serif;
    color: #1c1c1c;
}

.certificate-container {
    width: 100%;
    min-height: 92%;
    box-sizing: border-box;
    padding: 44px 50px;
    border: 10px double #a51c30;
    background: #fff;
    text-align: center;
}

.institution {
    margin: 12px 0 30px;
    font-size: 24pt;
    color: #a51c30;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.main-logo {
    width: 90px;
    height: 90px;
    object-fit: contain;
    margin-bottom: 6px;
}

.congratulations {
    font-size: 15pt;
    font-style: italic;
    margin-top: 10px;
}

.student-name {
    font-size: 34pt;
    margin: 18px 0 16px;
    padding: 0 30px 8px;
    border-bottom: 1px solid #bcbcbc;
    display: inline-block;
}

.completion-text {
    font-size: 13pt;
    line-height: 1.6;
    margin: 10px 42px;
}

.issue-date {
    margin-top: 16px;
    font-size: 11pt;
    color: #555;
}

.footer {
    margin-top: 52px;
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
    width: 170px;
    max-height: 80px;
    object-fit: contain;
    margin-bottom: 6px;
}

.signature-line {
    border-top: 1px solid #000;
    width: 220px;
    margin: 0 auto 6px;
}

.qr-code {
    width: 80px;
    height: 80px;
}

.serial {
    margin: 4px 0 0;
    font-size: 8pt;
    color: #666;
    word-break: break-all;
}
CSS;

        DB::table('certificate_templates')->updateOrInsert(
            ['slug' => 'harvard-style-default'],
            [
                'certification_id' => null,
                'name' => 'Harvard Style Default',
                'html_template' => $htmlTemplate,
                'css_template' => $cssTemplate,
                'is_default' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('certificate_templates')
            ->where('slug', 'harvard-style-default')
            ->delete();
    }
};
