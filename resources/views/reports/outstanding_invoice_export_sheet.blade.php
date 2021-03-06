<style>
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
</style>
<table class="" width="">
    @php
        $total_amount = 0;
    @endphp
    <thead>
        <tr>
            <th colspan="8" align="center" style="font-size: 20px;"><b>Outstanding Invoice Report</b></th>
        </tr>
        <tr>
            <th colspan="8" align="center"><b>{{ date('d-m-Y', strtotime($request->start_date)) . ' - ' . date('d-m-Y', strtotime($request->end_date)) }}</b></th>
        </tr>
        <tr>
            <th>Invoice No</th>
            <th>Inv Date</th>
            <th>Company</th>
            <th>Agent</th>
            <th align="right">Amount</th>
            <th>Status</th>
            <th>Due</th>
            <th>Generated by</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $d)
        @php
            if ($d->commission_status == 0) {
                $icon = 'x';
            } else if($d->commission_status == 1) {
                $icon = '✔';
            } else { // pending
                $icon = '...';
            }
            $total_amount += floatval($d->pending_commission);
        @endphp
        <tr>
            <td class="">  {{ $d->bill_no }} </td>
            <td> {{ $d->bill_date2 }} </td>
            <td> {{ $d->supplier_name ? $d->supplier_name : $d->customer_name }} </td>
            <td> {{ $d->agent_name }} </td>
            <td align="right"> {{ number_format($d->pending_commission) }} </td>
            <td> {{ $icon }} </td>
            <td> {{ $d->due_days }} </td>
            <td> {{ $d->employee_name }} </td>
        </tr>
        @endforeach
        <tr>
            <th colspan="4"> <b>Total</b></th>
            <th align="right"><b> {{ number_format($total_amount) }} </b></th>
            <th colspan="3"> </th>
        </tr>
    </tbody>
</table>
