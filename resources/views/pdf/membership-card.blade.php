<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'DejaVu Sans',sans-serif;
}

@page{
    size:A4;
    margin:0;
}

body{
    margin:0;
    padding:0;
    background:#fff;
}

/* ===========================
   CARD
=========================== */

.card,
.back-card{

    position:relative;

    width:85.6mm;
    height:53.98mm;

    margin:35mm auto 0;

    background:#fff;

    border:1px solid #d9d9d9;

    overflow:hidden;
}

.page-break{
    page-break-after:always;
}

/* ===========================
   RED HEADER
=========================== */

.top-red{

    width:100%;

    height:7mm;

    background:#c62828;
}

/* ===========================
   FRONT
=========================== */

.front-header{

    width:100%;

    border-collapse:collapse;

    padding:10px 12px;
}

.front-header td{
    vertical-align:middle;
}

.logo-cell{
    width:35%;
}

.logo-cell img{

    width:40mm;

    display:block;
}

.title-cell{

    width:45%;

    text-align:center;
}

.title-cell div{

    font-size:8px;

    font-weight:bold;

    line-height:1.3;
}

.qr-cell{

    width:20%;

    text-align:right;
}

.qr-code-img{

    width:18mm;

    height:18mm;

    border:1px solid #ddd;

    padding:1px;
}

/* ===========================
   DISCLAIMER
=========================== */

.disclaimer{

    background:#c62828;

    color:#fff;

    padding:8px 12px;

    height:100%;
}

.disclaimer p{

    color:#fff;

    font-size:6.5px;

    line-height:1.35;

    text-align:justify;
}

.phone{

    margin-top:4px;

    font-weight:bold;
}

/* ===========================
   BACK
=========================== */

.back-card{

    position:relative;
}

/* Red strip */

.back-card .top-red{

    position:absolute;

    top:0;

    left:0;

    right:0;
}

/* ===========================
   PHOTO
=========================== */

.photo-wrapper{

    position:absolute;

    left:8px;

    top:0mm;

    width:23mm;

    height:31mm;

    z-index:100;
}

.pilot-photo{

    width:23mm;

    height:27mm;

    display:block;

    border:1px solid #ddd;
}

/* ===========================
   PILOT INFO
=========================== */

.pilot-info{

    position:absolute;

    left:28mm;

    top:11mm;

    width:42mm;
}

.pilot-name{

    font-size:11px;

    font-weight:bold;

    color:#3e3d3d;

    margin-bottom:2px;
}

.pilot-discipline{

    font-size:8px;

    color:#666;

    text-transform:capitalize;

    margin-bottom:2px;
}

.pilot-dob{

    font-size:8px;

    color:#888;
}

/* ===========================
   DETAILS
=========================== */
.details-table{

    position:absolute;

    top:30mm;

    left:8px;

    width:69mm;

    border-collapse:collapse;

    table-layout:fixed;
}

.details-table td{

    vertical-align:top;

    width:34.5mm;

    padding-right:3mm;
}

.detail-item{

    margin-bottom:1mm;
}

.detail-label{

    font-size:8px;

   color:#5d5c5c;

    font-weight:bold;

    text-transform:capitalize;

    margin-bottom:0.8px;
}

.detail-value{

    font-size:8px;

    font-weight:bold;

  color:#888;
}

.green{

  color:#888;
}
    </style>
</head>
<body>

    <!-- ============================= -->
    <!-- FRONT OF CARD -->
    <!-- ============================= -->
    <div class="card">
        <div class="top-red"></div>

        <table class="front-header">
            <tr>
                <td class="logo-cell">
             <img  src="{{ public_path('logocard.png') }}" alt="LASF Logo">
                </td>
           
                <td class="qr-cell">
                    @if($qrCode)
                        <img src="{{ $qrCode }}" class="qr-code-img" alt="QR Code">
                    @endif
                </td>
            </tr>
        </table>

        <div class="disclaimer">
            <p>
                The holder of this card pledges to comply with and respect the safety standards and the instructions of the federation and the instructors. He fully understands the risks associated with this sport and releases the LASF from all liability, assuming full responsibility for any damages or losses to property or life, whether caused intentionally or unintentionally.
            </p>
            <p class="phone">
                For more information or to initiate a claim, please call <strong>+96171909008</strong>
            </p>
        </div>
    </div>

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- ============================= -->
    <!-- BACK OF CARD -->
    <!-- ============================= -->
    <div class="back-card">

        <div class="top-red"></div>

        <!-- PHOTO & INFO SIDE BY SIDE -->
<div class="photo-wrapper">
    <img src="{{ $photoPath }}" class="pilot-photo">
</div>

<div class="pilot-info">
    <div class="pilot-name">{{ $user->name }}</div>

<div class="pilot-discipline">
    {{
        $profile->disciplines && $profile->disciplines->count()
            ? $profile->disciplines->pluck('name')->implode(' | ')
            : 'No Active Disciplines'
    }}
</div>

<div class="pilot-dob">
    {{ $profile->date_of_birth
        ? \Carbon\Carbon::parse($profile->date_of_birth)->format('d/m/Y')
        : ''
    }}
</div>
</div>

        <!-- DETAILS TABLE -->
<table class="details-table">
    <tr>

        <td class="details-left">

            <div class="detail-item">
                <div class="detail-label">Member No.</div>
                <div class="detail-value">{{ $profile->license_number }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Blood Type</div>
                <div class="detail-value">{{ $profile->blood_type }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Ratings</div>
                <div class="detail-value">{{ $profile->ratings }}</div>
            </div>

        </td>

        <td class="details-right">

            <div class="detail-item">
                <div class="detail-label">Insurance</div>
                <div class="detail-value">
                    {{ $profile->insurance_provider }}
                    @if($profile->insurance_number)
                        #{{ $profile->insurance_number }}
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Club Member Of</div>
                <div class="detail-value">{{ $profile->club_name }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Valid Until</div>
                <div class="detail-value green">
                    {{ \Carbon\Carbon::parse($profile->valid_until)->format('d/m/Y') }}
                </div>
            </div>

        </td>

    </tr>
</table>

    </div>

</body>
</html>
