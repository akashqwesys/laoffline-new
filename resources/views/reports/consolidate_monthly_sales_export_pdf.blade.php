<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <!-- Fav Icon  -->
    <link rel="shortcut icon" href="/assets/images/favicon.png">
    <!-- Normalize or reset CSS with your favorite library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
    <!-- Load paper.css for happy printing -->
    <link rel="stylesheet" href="/assets/css/paper.css">
    <!-- Set page size here: A5, A4 or A3 -->
    <!-- Set also "landscape" if you need -->
    <style>
        /* @page {
            size: A4;
        } */
        * {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            -o-box-sizing: border-box;
        }
        article {
            padding: 10px 0;
        }
        .logo {
            text-align: center;
        }
        .logo > img {
            max-width: 150px;
        }
        /* table {
            border: 1px solid;
        } */
        table tr th {
            text-align: left;
        }
        table tr th,
        table tr td {
            font-size: 12px;
            padding: 3px;
            /* letter-spacing: 0px; */
            border: 1px solid black;
        }
        .clearfix {
            clear: both;
        }
        hr {
            border: 1px solid #222;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .mt-2 {
            margin-top: 0.5rem;
        }
        .mt-4 {
            margin-top: 1rem;
        }
        .mb-4 {
            margin-bottom: 1rem;
        }
        @media print {
            .content-block, p {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<!-- Set "A5", "A4" or "A3" for class name -->
<!-- Set also "landscape" if you need -->
<body class="A4">

    <!-- Each sheet element should have the class "sheet" -->
    <!-- "padding-**mm" is optional: you can set 10, 15, 20 or 25 -->
    <section class="sheet">
        <!-- Write HTML just like a web page -->
        <article>
            <div class="logo mb-4">
                <img src="https://dev.laoffline.com/assets/images/logo_report.png" alt="Logo">
            </div>
            <table class="" width="" align="center">
                @php
                    $total_payment = $total_received = $total_pending = 0;
                @endphp
                <thead>
                    <tr><th colspan="4" class="text-center" style="font-size: 18px;">MONTHLY SALES REPORT</th></tr>
                    <tr><th colspan="4" class="text-center" >{{ $request->customer['name'] ?? 'All Parties' }}</th></tr>
                    <tr><th colspan="4" class="text-center" >{{ date('d-m-Y', strtotime($request->start_date)) . ' TO ' . date('d-m-Y', strtotime($request->end_date)) }}</th></tr>
                    <tr><th colspan="4" class="text-center" >{{ $request->agent['name'] }}</th></tr>
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Gross Sales(Amt)</th>
                        <th class="text-right">Net Sales(Amt)</th>
                        <th class="text-right">Gross Pending(Amt)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $d)
                    @php
                        $total_payment += floatval($d->total_payment);
                        $total_received += floatval($d->total_received);
                        $total_pending += floatval($d->total_pending);
                    @endphp
                    <tr>
                        <td class=""> {{ $d->month_year }} </td>
                        <td class="text-right"> {{ number_format($d->total_payment) }} </td>
                        <td class="text-right"> {{ number_format($d->total_received) }} </td>
                        <td class="text-right"> {{ number_format($d->total_pending) }} </td>
                    </tr>
                    @endforeach
                    <tr>
                        <th class=""> Total </th>
                        <th class="text-right"> {{ number_format($total_payment) }} </th>
                        <th class="text-right"> {{ number_format($total_received) }} </th>
                        <th class="text-right"> {{ number_format($total_pending) }} </th>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
