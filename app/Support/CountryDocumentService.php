<?php

namespace App\Support;

class CountryDocumentService
{
    /**
     * @return array<int, string>
     */
    public static function countryCodes(): array
    {
        return [
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
            'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
            'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
            'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
            'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
            'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
            'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
            'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
            'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
            'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
            'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
            'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
            'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
            'VN', 'VU', 'WF', 'WS', 'XK', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function countryOptions(string $locale = 'es'): array
    {
        $options = [];

        foreach (self::countryCodes() as $code) {
            $options[$code] = self::countryName($code, $locale);
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function documentTypes(string $countryCode, string $locale = 'es'): array
    {
        $countryCode = strtoupper(trim($countryCode));
        $specific = self::countryDocumentRules()[$countryCode]['types'] ?? null;

        if (is_array($specific)) {
            $types = [];
            foreach ($specific as $type) {
                $types[$type] = self::documentTypeLabel($type, $locale);
            }

            return $types;
        }

        return [
            'NATIONAL_ID' => self::documentTypeLabel('NATIONAL_ID', $locale),
            'PASSPORT' => self::documentTypeLabel('PASSPORT', $locale),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function documentTypeMap(string $locale = 'es'): array
    {
        $map = [];

        foreach (self::countryCodes() as $code) {
            $map[$code] = self::documentTypes($code, $locale);
        }

        return $map;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function documentHintMap(): array
    {
        $map = [];

        foreach (self::countryCodes() as $countryCode) {
            $map[$countryCode] = [];
            foreach (array_keys(self::documentTypes($countryCode, 'es')) as $documentType) {
                $map[$countryCode][$documentType] = self::documentHint($countryCode, $documentType);
            }
        }

        return $map;
    }

    public static function documentHint(string $countryCode, string $documentType): string
    {
        $countryCode = strtoupper(trim($countryCode));
        $documentType = strtoupper(trim($documentType));

        $rules = self::countryDocumentRules()[$countryCode]['hints'] ?? [];
        if (isset($rules[$documentType])) {
            return $rules[$documentType];
        }

        return __('app.generic_document_format');
    }

    /**
     * @return array<int, string>
     */
    public static function specificFormatCountries(): array
    {
        return array_keys(self::countryDocumentRules());
    }

    public static function hasSpecificFormat(string $countryCode): bool
    {
        return in_array(strtoupper(trim($countryCode)), self::specificFormatCountries(), true);
    }

    public static function genericDocumentRegex(): string
    {
        return '/^[A-Z0-9]{5,30}$/';
    }

    public static function validationRegex(string $countryCode, string $documentType): string
    {
        $countryCode = strtoupper(trim($countryCode));
        $documentType = strtoupper(trim($documentType));

        $rules = self::countryDocumentRules()[$countryCode]['regex'] ?? [];
        if (isset($rules[$documentType])) {
            return $rules[$documentType];
        }

        return self::genericDocumentRegex();
    }

    public static function normalizeDocument(string $document): string
    {
        $normalized = mb_strtoupper(trim($document));

        return preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';
    }

    private static function countryName(string $countryCode, string $locale): string
    {
        $name = self::manualCountryName($countryCode, $locale);
        if ($name !== null) {
            return $name;
        }

        if (function_exists('locale_get_display_region')) {
            $generated = locale_get_display_region('-'.$countryCode, $locale);
            if (is_string($generated) && $generated !== '' && strtoupper($generated) !== strtoupper($countryCode)) {
                return $generated;
            }
        }

        return $countryCode;
    }

    private static function manualCountryName(string $countryCode, string $locale): ?string
    {
        $countryCode = strtoupper($countryCode);
        $isEs = str_starts_with(strtolower($locale), 'es');

        $namesEs = [
            'VE' => 'Venezuela',
            'CO' => 'Colombia',
            'AR' => 'Argentina',
            'BR' => 'Brasil',
            'ES' => 'España',
            'MX' => 'México',
            'US' => 'Estados Unidos',
            'CA' => 'Canadá',
            'CL' => 'Chile',
            'PE' => 'Perú',
            'EC' => 'Ecuador',
            'DO' => 'República Dominicana',
            'CR' => 'Costa Rica',
            'PA' => 'Panamá',
            'GT' => 'Guatemala',
            'HN' => 'Honduras',
            'NI' => 'Nicaragua',
            'PY' => 'Paraguay',
            'UY' => 'Uruguay',
            'BO' => 'Bolivia',
            'SV' => 'El Salvador',
            'PR' => 'Puerto Rico',
        ];

        $namesEn = [
            'VE' => 'Venezuela',
            'CO' => 'Colombia',
            'AR' => 'Argentina',
            'BR' => 'Brazil',
            'ES' => 'Spain',
            'MX' => 'Mexico',
            'US' => 'United States',
            'CA' => 'Canada',
            'CL' => 'Chile',
            'PE' => 'Peru',
            'EC' => 'Ecuador',
            'DO' => 'Dominican Republic',
            'CR' => 'Costa Rica',
            'PA' => 'Panama',
            'GT' => 'Guatemala',
            'HN' => 'Honduras',
            'NI' => 'Nicaragua',
            'PY' => 'Paraguay',
            'UY' => 'Uruguay',
            'BO' => 'Bolivia',
            'SV' => 'El Salvador',
            'PR' => 'Puerto Rico',
        ];

        $map = $isEs ? $namesEs : $namesEn;

        return $map[$countryCode] ?? null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function specificDocumentTypes(): array
    {
        return [
            'VE' => ['V', 'E', 'PASSPORT'],
            'CO' => ['CC', 'CE', 'PASSPORT'],
            'AR' => ['DNI', 'PASSPORT'],
            'BR' => ['CPF', 'PASSPORT'],
            'ES' => ['DNI', 'NIE', 'PASSPORT'],
            'MX' => ['CURP', 'INE', 'PASSPORT'],
            'US' => ['SSN', 'PASSPORT'],
            'CA' => ['SIN', 'PASSPORT'],
            'CL' => ['RUT', 'RUN', 'PASSPORT'],
            'PE' => ['DNI', 'CE', 'PASSPORT'],
            'EC' => ['CI', 'PASSPORT'],
            'DO' => ['CEDULA', 'PASSPORT'],
            'CR' => ['CEDULA', 'PASSPORT'],
            'PA' => ['CEDULA', 'PASSPORT'],
            'GT' => ['CUI', 'PASSPORT'],
            'HN' => ['DNI', 'PASSPORT'],
            'NI' => ['CEDULA', 'PASSPORT'],
            'PY' => ['CI', 'PASSPORT'],
            'UY' => ['CI', 'PASSPORT'],
            'BO' => ['CI', 'PASSPORT'],
            'SV' => ['DUI', 'PASSPORT'],
            'PR' => ['SSN', 'PASSPORT'],
        ];
    }

    /**
     * @return array<string, array{types: array<string>, hints: array<string, string>, regex: array<string, string>}>
     */
    private static function countryDocumentRules(): array
    {
        return [
            'VE' => [
                'types' => ['V', 'E', 'PASSPORT'],
                'hints' => [
                    'V' => 'V-12.345.678',
                    'E' => 'E-5.444.555',
                    'PASSPORT' => 'P1234567',
                ],
                'regex' => [
                    'V' => '/^V-\d{1,2}\.\d{3}\.\d{3}$/',
                    'E' => '/^E-\d{1,2}\.\d{3}\.\d{3}$/',
                    'PASSPORT' => '/^[A-Z]\d{6,9}$/',
                ],
            ],
            'CO' => [
                'types' => ['CC', 'CE', 'PASSPORT'],
                'hints' => [
                    'CC' => 'CC-1.234.567.890',
                    'CE' => 'CE-123456789',
                    'PASSPORT' => 'PA1234567',
                ],
                'regex' => [
                    'CC' => '/^CC-\d{6,12}$/',
                    'CE' => '/^CE-\d{6,12}$/',
                    'PASSPORT' => '/^[A-Z]{2}\d{7}$/',
                ],
            ],
            'AR' => [
                'types' => ['DNI', 'PASSPORT'],
                'hints' => [
                    'DNI' => '12.345.678',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'DNI' => '/^\d{1,2}\.\d{3}\.\d{3}$/',
                    'PASSPORT' => '/^[A-Z]\d{6,9}$/',
                ],
            ],
            'BR' => [
                'types' => ['CPF', 'PASSPORT'],
                'hints' => [
                    'CPF' => '123.456.789-09',
                    'PASSPORT' => 'AB1234567',
                ],
                'regex' => [
                    'CPF' => '/^\d{3}\.\d{3}\.\d{3}-\d{2}$/',
                    'PASSPORT' => '/^[A-Z]{2}\d{7}$/',
                ],
            ],
            'ES' => [
                'types' => ['DNI', 'NIE', 'PASSPORT'],
                'hints' => [
                    'DNI' => '12345678Z',
                    'NIE' => 'X1234567L',
                    'PASSPORT' => 'X1234567',
                ],
                'regex' => [
                    'DNI' => '/^\d{8}[A-Z]$/',
                    'NIE' => '/^[XYZ]\d{7}[A-Z]$/',
                    'PASSPORT' => '/^[A-Z0-9]{8,12}$/',
                ],
            ],
            'MX' => [
                'types' => ['CURP', 'INE', 'PASSPORT'],
                'hints' => [
                    'CURP' => 'GOCJ800101HDFRRN09',
                    'INE' => 'ABC123456HDFRRN01',
                    'PASSPORT' => 'G12345678',
                ],
                'regex' => [
                    'CURP' => '/^[A-Z]{4}\d{6}[A-Z]{6}\d{2}$/',
                    'INE' => '/^[A-Z0-9]{12,20}$/',
                    'PASSPORT' => '/^[A-Z]\d{8}$/',
                ],
            ],
            'US' => [
                'types' => ['SSN', 'PASSPORT'],
                'hints' => [
                    'SSN' => '123-45-6789',
                    'PASSPORT' => '123456789',
                ],
                'regex' => [
                    'SSN' => '/^\d{3}-\d{2}-\d{4}$/',
                    'PASSPORT' => '/^[A-Z0-9]{6,9}$/',
                ],
            ],
            'CA' => [
                'types' => ['SIN', 'PASSPORT'],
                'hints' => [
                    'SIN' => '123-456-789',
                    'PASSPORT' => 'AB1234567',
                ],
                'regex' => [
                    'SIN' => '/^\d{3}-\d{3}-\d{3}$/',
                    'PASSPORT' => '/^[A-Z0-9]{8,9}$/',
                ],
            ],
            'CL' => [
                'types' => ['RUT', 'RUN', 'PASSPORT'],
                'hints' => [
                    'RUT' => '12.345.678-5',
                    'RUN' => '12.345.678-5',
                    'PASSPORT' => 'AB1234567',
                ],
                'regex' => [
                    'RUT' => '/^\d{1,2}\.\d{3}\.\d{3}-[\dkK]$/',
                    'RUN' => '/^\d{1,2}\.\d{3}\.\d{3}-[\dkK]$/',
                    'PASSPORT' => '/^[A-Z0-9]{8,9}$/',
                ],
            ],
            'PE' => [
                'types' => ['DNI', 'CE', 'PASSPORT'],
                'hints' => [
                    'DNI' => '12345678',
                    'CE' => '123456789',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'DNI' => '/^\d{8}$/',
                    'CE' => '/^\d{9}$/',
                    'PASSPORT' => '/^[A-Z]\d{6,9}$/',
                ],
            ],
            'EC' => [
                'types' => ['CI', 'PASSPORT'],
                'hints' => [
                    'CI' => '123-456-7890',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CI' => '/^\d{3}-\d{3}-\d{4}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'DO' => [
                'types' => ['CEDULA', 'PASSPORT'],
                'hints' => [
                    'CEDULA' => '001-1234567-8',
                    'PASSPORT' => 'PP1234567',
                ],
                'regex' => [
                    'CEDULA' => '/^\d{3}-\d{7}-\d$/',
                    'PASSPORT' => '/^[A-Z]{2}\d{7}$/',
                ],
            ],
            'CR' => [
                'types' => ['CEDULA', 'PASSPORT'],
                'hints' => [
                    'CEDULA' => '1-2345-6789',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CEDULA' => '/^\d-\d{4}-\d{4}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'PA' => [
                'types' => ['CEDULA', 'PASSPORT'],
                'hints' => [
                    'CEDULA' => '8-123-456',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CEDULA' => '/^\d-\d{3}-\d{3}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'GT' => [
                'types' => ['CUI', 'PASSPORT'],
                'hints' => [
                    'CUI' => '1234 56789 0123',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CUI' => '/^\d{4}\s\d{5}\s\d{4}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'HN' => [
                'types' => ['DNI', 'PASSPORT'],
                'hints' => [
                    'DNI' => '0801-1980-12345',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'DNI' => '/^\d{4}-\d{4}-\d{5}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'NI' => [
                'types' => ['CEDULA', 'PASSPORT'],
                'hints' => [
                    'CEDULA' => '001-123456-0001X',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CEDULA' => '/^\d{3}-\d{6}-\d{4}[A-Z]?$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'PY' => [
                'types' => ['CI', 'PASSPORT'],
                'hints' => [
                    'CI' => '1234567',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CI' => '/^\d{5,8}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'UY' => [
                'types' => ['CI', 'PASSPORT'],
                'hints' => [
                    'CI' => '1.234.567-8',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CI' => '/^\d\.\d{3}\.\d{3}-\d$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'BO' => [
                'types' => ['CI', 'PASSPORT'],
                'hints' => [
                    'CI' => '1234567',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'CI' => '/^\d{5,8}$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'SV' => [
                'types' => ['DUI', 'PASSPORT'],
                'hints' => [
                    'DUI' => '01234567-8',
                    'PASSPORT' => 'A1234567',
                ],
                'regex' => [
                    'DUI' => '/^\d{8}-\d$/',
                    'PASSPORT' => '/^[A-Z]\d{7}$/',
                ],
            ],
            'PR' => [
                'types' => ['SSN', 'PASSPORT'],
                'hints' => [
                    'SSN' => '123-45-6789',
                    'PASSPORT' => '123456789',
                ],
                'regex' => [
                    'SSN' => '/^\d{3}-\d{2}-\d{4}$/',
                    'PASSPORT' => '/^[A-Z0-9]{6,9}$/',
                ],
            ],
        ];
    }

    private static function documentTypeLabel(string $documentType, string $locale): string
    {
        $documentType = strtoupper($documentType);
        $isEs = str_starts_with(strtolower($locale), 'es');

        return match ($documentType) {
            'V' => 'V - '.($isEs ? 'Venezolano' : 'Venezuelan'),
            'E' => 'E - '.($isEs ? 'Extranjero' : 'Foreigner'),
            'CC' => 'CC - '.($isEs ? 'Cedula de ciudadania' : 'Citizen ID'),
            'CE' => 'CE - '.($isEs ? 'Cedula de extranjeria' : 'Foreigner ID'),
            'DNI' => 'DNI - '.($isEs ? 'Documento nacional' : 'National ID'),
            'NIE' => 'NIE - '.($isEs ? 'Numero de identidad de extranjero' : 'Foreigner identity number'),
            'CPF' => 'CPF - '.($isEs ? 'Registro fiscal' : 'Tax ID'),
            'CURP' => 'CURP - '.($isEs ? 'Clave unica de registro' : 'Unique population code'),
            'INE' => 'INE - '.($isEs ? 'Credencial electoral' : 'Voter credential'),
            'SSN' => 'SSN - '.($isEs ? 'Seguro social' : 'Social security number'),
            'RUT' => 'RUT - '.($isEs ? 'Rol unico tributario' : 'Tax ID number'),
            'RUN' => 'RUN - '.($isEs ? 'Rol unico nacional' : 'National ID number'),
            'SIN' => 'SIN - '.($isEs ? 'Numero de seguro social' : 'Social insurance number'),
            'CI' => 'CI - '.($isEs ? 'Cedula de identidad' : 'Identity card'),
            'CEDULA' => 'Cédula - '.($isEs ? 'Documento nacional' : 'National document'),
            'CUI' => 'CUI - '.($isEs ? 'Codigo unico de identidad' : 'Unique identity code'),
            'DUI' => 'DUI - '.($isEs ? 'Documento unico de identidad' : 'Unique identity document'),
            'PASSPORT' => $isEs ? 'Pasaporte' : 'Passport',
            'NATIONAL_ID' => $isEs ? 'Documento nacional' : 'National ID',
            default => $documentType,
        };
    }
}
