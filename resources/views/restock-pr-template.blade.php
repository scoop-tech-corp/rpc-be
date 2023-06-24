<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0px;
            font-size: 20px;
            /* font-family: Calibri; */
        }

        body {
            margin: 0px;
            font-size: 20px;
            font-family: Arial, Helvetica, sans-serif;
        }

        th.colJumlah {
            text-align: left;
            width: 15%;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            border-collapse: collapse;
            font-size: small;
            background-color: black;
            color: white;
        }

        th.colProduk {
            text-align: left;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            border-collapse: collapse;
            width: 35%;
            font-size: small;
            background-color: black;
            color: white;
        }

        th.colAmount {
            text-align: left;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            border-collapse: collapse;
            width: 25%;
            font-size: small;
            background-color: black;
            color: white;
        }

        th.colTotal {
            text-align: left;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            border-collapse: collapse;
            width: 25%;
            font-size: small;
            background-color: black;
            color: white;
        }

        td.removeBorder {
            border-bottom: hidden !important;
            border-left: hidden !important;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            padding: 12px;
            margin: 0px;
        }

        th {
            height: 10px;
        }

        label.title {
            font-weight: bold;
            font-size: 25px
        }

        label.titlePetName {
            font-weight: bold;
            font-size: 20px
        }

        label.address {
            font-size: 15px
        }

        td.date {
            text-align: right;
            font-size: 15px;
        }

        td.codeEmployee {
            text-align: right;
            font-size: 15px;
            border-bottom: 1px solid black;
            border-collapse: collapse;
        }

        .row {
            margin-left: -5px;
            margin-right: -5px;
        }

        .column {
            float: left;
            width: 50%;
            padding: 5px;
        }

        .row::after {
            content: "";
            clear: both;
            display: table;
        }

        table.rounded {
            border-collapse: separate;
            border: solid black 1px;
            border-radius: 8px;
        }

        table.rounded td {
            border-left: solid black 1px;
        }

        table.rounded td:first-child,
        table.rounded th:first-child {
            border-left: none;
        }

        table.footer {
            border-collapse: collapse;
        }
    </style>
</head>


<body>
    <table style="width: 100%">
        <tr>
            <td style="width:30%">
                <img src="{{ public_path() . '/asset/logo-rpc-full.png'}}" width="130px" height="130px" alt="test">

            </td>
            <td>
                <label class="title">PURCHASE REQUEST</label>
            </td>
            <td>
            </td>
        </tr>
    </table>

    <br>

    <div class="row">
        <div class="column">

            <table style="width: 100%">
                <tr>
                    <td style="width:30%">
                        <label>Supplier:</label>
                    </td>
                    <td style="width:50%">
                        <label>{{$dataSupplier->supplierName}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>PIC:</label>
                    </td>
                    <td style="width:50%">
                        <label>{{$dataSupplier->pic}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Alamat:</label>
                    </td>
                    <td style="width:50%">
                        <label>{{$dataSupplier->streetAddress}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Provinsi:</label>
                    </td>

                    @php
                    $provinsi = '-';
                        @if (!is_null($dataSupplier->provinsi))
                            $provinsi = $dataSupplier->provinsi;
                        @endif
                    @endphp

                    <td style="width:50%">
                        <label>{{$provinsi}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Kota:</label>
                    </td>

                    @php
                    $kota = '-';
                        @if (!is_null($dataSupplier->kota))
                            $kota = $dataSupplier->kota;
                        @endif
                    @endphp


                    <td style="width:50%">
                        <label>{{$kota}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Kode POS:</label>
                    </td>

                    @php
                    $postalCode = '-';
                        @if (!is_null($dataSupplier->postalCode))
                            $postalCode = $dataSupplier->postalCode;
                        @endif
                    @endphp

                    <td style="width:50%">
                        <label>{{$dataSupplier->postalCode}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>No. Telp:</label>
                    </td>
                    <td style="width:50%">
                        @if(is_null($dataWhatsApp))
                        <label>-</label>
                        @else
                        <label>{{$dataWhatsApp->number}}</label>
                        @endif

                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Fax:</label>
                    </td>
                    <td style="width:50%">
                        @if(is_null($dataFax))
                        <label>-</label>
                        @else
                        <label>{{$dataFax->number}}</label>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>No. Telp PIC:</label>
                    </td>
                    <td style="width:50%">
                        @if(is_null($dataPic))
                        <label>-</label>
                        @else
                        <label>{{$dataPic->number}}</label>
                        @endif
                    </td>
                </tr>

            </table>
        </div>

        <div class="column">
            <table style="width: 90%">

                <tr>
                    <td>
                        <label>No. PR:</label>
                    </td>
                    <td>
                        <label>{{$data[0]['purchaseRequestNumber']}}</label>
                    </td>
                </tr>
                <tr>
                    <td style="width:20%">
                        <label>Tanggal:</label>
                    </td>
                    <td style="width:50%">
                        <label>{{$dataMaster->createdAt}}</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Lokasi:</label>
                    </td>
                    <td>
                        <label>{{$dataMaster->locationName}}</label>
                    </td>
                </tr>


            </table>
            <br>
            <table class="rounded" style="width:80%">
                <tr>
                    <td style="width:50%">
                        <label>Pastikan Purchase Request harus disetujui dahulu sebelum Purchase Order diterbitkan</label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <br>

    <table border="2">
        <tr>
            <td>
                <label><b>Pemohon</b></label>
            </td>
            <td>
                <label><b>Nomor Telepon</b></label>
            </td>
            <td>
                <label><b>Jabatan</b></label>
            </td>
        </tr>
        <tr>
            <td>
                <label>{{$dataMaster->createdBy}}</label>
            </td>
            <td>
                <label>{{$dataMaster->phoneNumber}}</label>
            </td>
            <td>
                <label>{{$dataMaster->roleName}}</label>
            </td>
        </tr>
    </table>

    <br>

    <table style="width:100%;" border="2">

        <tr>
            <th class="colJumlah">
                <label><b>No.</b></label>
                </td>
            </th>
            <th class="colJumlah">
                <label><b>Jumlah</b></label>
                </td>
            </th>
            <th class="colProduk">
                <label><b>Nama Produk</b></label>
            </th>
            <th class="colAmount">
                <label><b>Harga Satuan</b></label>
            </th>
            <th class="colTotal">
                <label><b>Total</b></label>
            </th>
        </tr>

        @php
        $num = 1;
        $total = 0;
        @endphp

        @foreach($data as $list)
        <tr>
            <td style="width: 9%">
                <label>{{$num}}</label>
            </td>
            <td style="width: 9%">
                <label>{{$list['quantity']}}</label>
            </td>
            <td style="width: 9%">
                <label>{{$list['fullName']}}</label>
            </td>
            <td style="width: 9%">
                <label>{{$list['costPerItem']}}</label>
            </td>
            <td style="width: 9%">
                <label>{{$list['total']}}</label>
            </td>
        </tr>
        @php
        $num++;
        $total = $total + $list['total'];
        @endphp
        @endforeach
        <tr class="no-bottom-border">
            <td class="removeBorder"></td>
            <td class="removeBorder"></td>
            <td class="removeBorder"></td>
            <td style="width: 9%; text-align:center">
                <label><b>Total</b></label>
            </td>
            <td><b>{{$total}}</b></td>

        </tr>
    </table>

    <br>

    <table style="width:100%;" border="2">
        <tr>
            <td style="width:35%">
                <label>Tanggal Pemesanan:</label>
            </td>
            <td>
                <label>12/12/2022</label>
            </td>
        </tr>
        <tr>
            <td>
                <label>No. PO:</label>
            </td>
            <td>
                <label>123</label>
            </td>
        </tr>
        <tr>
            <td>
                <label>Disetujui Oleh:</label>
            </td>
            <td>
                <label>asd</label>
            </td>
        </tr>
    </table>
</body>

</html>
