<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Visitor Information Card
    </title>

    <style>

        @page {
            size: A4 portrait;
            margin: 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .logo {
            width: 95px;
            margin-bottom: 5px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .subtitle {
            font-size: 11px;
            font-weight: bold;
        }

        .club-row {
            width: 100%;
            margin-bottom: 8px;
        }

        .club-row table {
            width: 100%;
            border-collapse: collapse;
        }

        .club-row td {
            border: none;
            padding: 3px;
        }

        .section-title {
            background: #e5e5e5;
            border: 1px solid #777;
            padding: 5px;
            font-weight: bold;
            font-size: 10px;
            margin-top: 8px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.info td,
        table.info th {
            border: 1px solid #777;
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            font-size: 8px;
        }

        .value {
            font-size: 9px;
            min-height: 13px;
        }

        .lasf-section {
            margin-bottom: 10px;
        }

        .lasf-title {
            background: #198754;
            color: white;
            padding: 6px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .signature-box {
            height: 45px;
        }

        .declaration {
            margin-top: 10px;
            border-top: 1px solid #777;
            padding-top: 7px;
            font-size: 8px;
            line-height: 1.5;
        }

        .arabic-note {
            margin-top: 12px;
            border-top: 1px solid #777;
            padding-top: 8px;
            font-size: 8px;
            line-height: 1.5;
            direction: rtl;
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .empty {
            min-height: 15px;
        }

    </style>

</head>

<body>

    {{-- ============================================================
         HEADER
    ============================================================= --}}

    <div class="header">

        @php
            $logoPath = public_path('assets/images/logocard.png');
        @endphp

        @if(file_exists($logoPath))
            <img
                src="{{ $logoPath }}"
                class="logo"
                alt="LASF"
            >
        @endif

        <div class="title">
            Visitor Information Card
        </div>

        <div class="subtitle">
            Dedicated to request permission to fly paragliding
        </div>

    </div>


    {{-- ============================================================
         CLUB
    ============================================================= --}}

    <div class="club-row">

        <table>

            <tr>

                <td style="width: 50%;">
                    <strong>Club:</strong>
                    {{ $profile->club_name ?? 'N/A' }}
                </td>

                <td style="width: 50%; text-align: right;">
                    <strong>LASF License Number:</strong>
                    {{ $profile->license_number ?? 'N/A' }}
                </td>

            </tr>

        </table>

    </div>


    {{-- ============================================================
         LASF PILOT INFORMATION
    ============================================================= --}}

    <div class="lasf-section">

        <div class="lasf-title">
            LASF Pilot Information
        </div>

        <table class="info">

            <tr>

                <td>
                    <div class="label">Full Name</div>
                    <div class="value">
                        {{ $pilot->name ?? 'N/A' }}
                    </div>
                </td>

                <td>
                    <div class="label">Phone Number</div>
                    <div class="value">
                        {{ $pilot->phone ?? 'N/A' }}
                    </div>
                </td>

                <td>
                    <div class="label">Email</div>
                    <div class="value">
                        {{ $pilot->email ?? 'N/A' }}
                    </div>
                </td>

            </tr>

            <tr>

                <td>
                    <div class="label">Blood Type</div>
                    <div class="value">
                        {{ $profile->blood_type ?? 'N/A' }}
                    </div>
                </td>

                <td>
                    <div class="label">Insurance Provider</div>
                    <div class="value">
                        {{ $profile->insurance_provider ?? 'N/A' }}
                    </div>
                </td>

                <td>
                    <div class="label">Insurance Number</div>
                    <div class="value">
                        {{ $profile->insurance_number ?? 'N/A' }}
                    </div>
                </td>

            </tr>

            <tr>

                <td colspan="3">

                    <div class="label">
                        Ratings / Disciplines
                    </div>

                    <div class="value">

                        @php
                            $ratings = $profile->ratings ?? [];

                            if (is_string($ratings)) {
                                $ratings = json_decode($ratings, true) ?? [];
                            }

                            $disciplineNames = [];

                            if (
                                isset($profile->disciplines)
                                && $profile->disciplines
                            ) {
                                $disciplineNames =
                                    $profile->disciplines
                                        ->pluck('name')
                                        ->toArray();
                            }
                        @endphp

                        {{ implode(', ', $ratings) }}

                        @if(count($disciplineNames))
                            @if(!empty($ratings))
                                |
                            @endif

                            {{ implode(', ', $disciplineNames) }}
                        @endif

                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ============================================================
         PERSONAL INFORMATION
    ============================================================= --}}

    <div class="section-title">
        Personal Information
    </div>

    <table class="info">

        <tr>

            <td>
                <div class="label">First Name</div>
                <div class="value">
                    {{ $international->first_name ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Father Name</div>
                <div class="value">
                    {{ $international->father_name ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Last Name</div>
                <div class="value">
                    {{ $international->last_name ?? '' }}
                </div>
            </td>

        </tr>

        <tr>

            <td>
                <div class="label">Mother's Full Name</div>
                <div class="value">
                    {{ $international->mother_full_name ?? '' }}
                </div>
            </td>

            <td colspan="2">
                <div class="label">Wife's Full Name</div>
                <div class="value">
                    {{ $international->wife_full_name ?? '' }}
                </div>
            </td>

        </tr>

        <tr>

            <td>
                <div class="label">Date Of Birth</div>
                <div class="value">
                    @if($international->date_of_birth)
                        {{ \Carbon\Carbon::parse($international->date_of_birth)->format('d/m/Y') }}
                    @endif
                </div>
            </td>

            <td>
                <div class="label">Place of Birth</div>
                <div class="value">
                    {{ $international->place_of_birth ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Nationality</div>
                <div class="value">
                    {{ $international->nationality ?? '' }}
                </div>
            </td>

        </tr>

        <tr>

            <td>
                <div class="label">Sex</div>
                <div class="value">
                    {{ $international->sex ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Marital Status</div>
                <div class="value">
                    {{ $international->marital_status ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Languages Fluently Spoken</div>
                <div class="value">
                    {{ $international->languages ?? '' }}
                </div>
            </td>

        </tr>

    </table>


    {{-- ============================================================
         IDENTIFICATION
    ============================================================= --}}

    <div class="section-title">
        Identification
    </div>

    <table class="info">

        <tr>

            <td>
                <div class="label">
                    National ID Number
                </div>

                <div class="value">
                    {{ $international->national_id_number ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">
                    File Number
                </div>

                <div class="value">
                    {{ $international->file_number ?? '' }}
                </div>
            </td>

        </tr>

        <tr>

            <td>
                <div class="label">
                    Passport Number
                </div>

                <div class="value">
                    {{ $international->passport_number ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">
                    Passport Issuing Authority
                </div>

                <div class="value">
                    {{ $international->passport_issuing_authority ?? '' }}
                </div>
            </td>

        </tr>

        <tr>

            <td>
                <div class="label">
                    Passport Issued Date
                </div>

                <div class="value">

                    @if($international->passport_issued_date)
                        {{ \Carbon\Carbon::parse($international->passport_issued_date)->format('d/m/Y') }}
                    @endif

                </div>
            </td>

            <td>
                <div class="label">
                    Passport Expiry Date
                </div>

                <div class="value">

                    @if($international->passport_expiry_date)
                        {{ \Carbon\Carbon::parse($international->passport_expiry_date)->format('d/m/Y') }}
                    @endif

                </div>
            </td>

        </tr>

    </table>


    {{-- ============================================================
         CONTACT INFORMATION
    ============================================================= --}}

    <div class="section-title">
        Contact Information
    </div>

    <table class="info">

        <tr>

            <td>
                <div class="label">
                    Mobile Phone #
                </div>

                <div class="value">
                    {{ $international->mobile_phone ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">
                    Alternative Phone #
                </div>

                <div class="value">
                    {{ $international->alternative_phone ?? '' }}
                </div>
            </td>

        </tr>

        <tr>

            <td>
                <div class="label">
                    Work Email Address
                </div>

                <div class="value">
                    {{ $international->work_email ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">
                    Personal Email Address
                </div>

                <div class="value">
                    {{ $international->personal_email ?? '' }}
                </div>
            </td>

        </tr>

    </table>


    {{-- ============================================================
         CURRENT ADDRESS
    ============================================================= --}}

    <div class="section-title">
        Current Address
    </div>

    <table class="info">

        <tr>

            <td>
                <div class="label">Country</div>
                <div class="value">
                    {{ $international->current_country ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">City</div>
                <div class="value">
                    {{ $international->current_city ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Street / Area</div>
                <div class="value">
                    {{ $international->current_street_area ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Building</div>
                <div class="value">
                    {{ $international->current_building ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Floor</div>
                <div class="value">
                    {{ $international->current_floor ?? '' }}
                </div>
            </td>

        </tr>

    </table>


    {{-- ============================================================
         ALTERNATIVE ADDRESS
    ============================================================= --}}

    <div class="section-title">
        Alternative Address
    </div>

    <table class="info">

        <tr>

            <td>
                <div class="label">Country</div>
                <div class="value">
                    {{ $international->alternative_country ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">City</div>
                <div class="value">
                    {{ $international->alternative_city ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Street / Area</div>
                <div class="value">
                    {{ $international->alternative_street_area ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Building</div>
                <div class="value">
                    {{ $international->alternative_building ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">Floor</div>
                <div class="value">
                    {{ $international->alternative_floor ?? '' }}
                </div>
            </td>

        </tr>

    </table>


    {{-- ============================================================
         EMPLOYMENT
    ============================================================= --}}

    <div class="section-title">
        Employment
    </div>

    <table class="info">

        <tr>

            <td>
                <div class="label">
                    Name Of Employer
                </div>

                <div class="value">
                    {{ $international->employer_name ?? '' }}
                </div>
            </td>

            <td>
                <div class="label">
                    Current Title
                </div>

                <div class="value">
                    {{ $international->current_title ?? '' }}
                </div>
            </td>

        </tr>

    </table>


    {{-- ============================================================
         CRIMINAL RECORD
    ============================================================= --}}

    <div class="section-title">
        Criminal Record
    </div>

    <table class="info">

        <tr>

            <td>

                <div class="label">
                    Have you ever been convicted of a crime?
                </div>

                <div class="value">

                    {{ $international->convicted_of_crime ? 'Yes' : 'No' }}

                </div>

            </td>

            <td>

                <div class="label">
                    If yes, specify crime type
                </div>

                <div class="value">

                    {{ $international->crime_type ?? '' }}

                </div>

            </td>

            <td>

                <div class="label">
                    Date
                </div>

                <div class="value">

                    @if($international->crime_date)
                        {{ \Carbon\Carbon::parse($international->crime_date)->format('d/m/Y') }}
                    @endif

                </div>

            </td>

        </tr>

    </table>


    {{-- ============================================================
         VISITOR SIGNATURE
    ============================================================= --}}

    <div class="section-title">
        Visitor Certification
    </div>

    <table class="info">

        <tr>

            <td style="width: 50%;">

                <div class="label">
                    Date
                </div>

                <div class="value">
                    {{ now()->format('d/m/Y') }}
                </div>

            </td>

            <td style="width: 50%;">

                <div class="label">
                    Visitor Name & Signature
                </div>

                <div class="value signature-box">

                    {{ $international->visitor_name ?? '' }}

                </div>

            </td>

        </tr>

    </table>


    {{-- ============================================================
         DECLARATION
    ============================================================= --}}

    <div class="declaration">

        <strong>
            Declaration:
        </strong>

        I hereby certify that all the preceding information
        is correct and accurate.

    </div>




</body>

</html>