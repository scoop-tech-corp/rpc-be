<?php

namespace Database\Seeders\Service;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus data test/dummy yang ada
        DB::table('contract_templates')->whereIn('id', [1, 2, 3])->delete();

        $now    = now();
        $userId = 1;

        $policies = [
            [
                'title'   => 'Syarat & Ketentuan Penitipan Pet Hotel',
                'version' => '1.0',
                'status'  => 'active',
                'content' => <<<HTML
<h2>SYARAT &amp; KETENTUAN PENITIPAN PET HOTEL</h2>
<h3>Radhiyan Pet and Care</h3>

<p>Dengan menyerahkan hewan peliharaan Anda kepada Radhiyan Pet and Care, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan berikut:</p>

<h4>1. Persyaratan Umum</h4>
<ol>
  <li>Hewan peliharaan yang dititipkan harus dalam kondisi sehat dan bebas dari penyakit menular.</li>
  <li>Pemilik wajib membawa <strong>buku vaksinasi</strong> yang masih berlaku (terutama vaksin rabies dan distemper).</li>
  <li>Hewan yang belum divaksinasi atau vaksinasinya tidak lengkap dapat ditolak demi keselamatan hewan lain.</li>
  <li>Pemilik wajib memberikan informasi yang akurat mengenai kondisi kesehatan, alergi, dan kebiasaan hewan.</li>
</ol>

<h4>2. Check-In &amp; Check-Out</h4>
<ol>
  <li>Jadwal check-in: <strong>08.00 – 18.00 WIB</strong> (hari kerja dan hari libur).</li>
  <li>Jadwal check-out: <strong>08.00 – 17.00 WIB</strong>.</li>
  <li>Keterlambatan pengambilan melebihi jadwal check-out akan dikenakan biaya menginap tambahan.</li>
  <li>Apabila hewan tidak diambil dalam <strong>3 x 24 jam</strong> setelah jadwal check-out tanpa konfirmasi, Radhiyan Pet and Care berhak menghubungi kontak darurat yang terdaftar.</li>
</ol>

<h4>3. Tanggung Jawab Pemilik</h4>
<ol>
  <li>Pemilik bertanggung jawab atas segala biaya perawatan, termasuk biaya medis apabila hewan memerlukan penanganan khusus selama penitipan.</li>
  <li>Pemilik wajib menyediakan makanan khusus apabila hewan memiliki diet tertentu.</li>
  <li>Pemilik harus dapat dihubungi selama hewan dititipkan atau menunjuk kontak darurat yang dapat dihubungi.</li>
</ol>

<h4>4. Keamanan &amp; Kenyamanan</h4>
<ol>
  <li>Radhiyan Pet and Care akan berusaha semaksimal mungkin menjaga keamanan dan kenyamanan hewan selama penitipan.</li>
  <li>Hewan akan mendapatkan makan minimal 2x sehari dan air minum segar sepanjang hari.</li>
  <li>Kandang dibersihkan setiap hari untuk menjaga kebersihan dan higienitas.</li>
</ol>
HTML,
            ],

            [
                'title'   => 'Kebijakan Kesehatan & Penanganan Darurat',
                'version' => '1.0',
                'status'  => 'active',
                'content' => <<<HTML
<h2>KEBIJAKAN KESEHATAN &amp; PENANGANAN DARURAT</h2>
<h3>Radhiyan Pet and Care</h3>

<p>Demi menjaga kesehatan seluruh hewan yang dititipkan, Radhiyan Pet and Care menerapkan kebijakan kesehatan sebagai berikut:</p>

<h4>1. Pemeriksaan Saat Check-In</h4>
<ol>
  <li>Setiap hewan yang datang akan diperiksa kondisi fisiknya oleh petugas sebelum masuk kandang.</li>
  <li>Apabila ditemukan tanda-tanda penyakit menular (jamur, kudis, diare, dll.), hewan <strong>tidak dapat dititipkan</strong> hingga dinyatakan sembuh oleh dokter hewan.</li>
  <li>Dokumen vaksinasi yang kadaluarsa tidak dapat diterima.</li>
</ol>

<h4>2. Penanganan Kondisi Darurat</h4>
<ol>
  <li>Apabila hewan mengalami kondisi darurat (sakit mendadak, kecelakaan, dll.), petugas akan segera menghubungi pemilik.</li>
  <li>Apabila pemilik <strong>tidak dapat dihubungi</strong> dan kondisi hewan membutuhkan tindakan segera, Radhiyan Pet and Care berhak mengambil tindakan medis yang diperlukan demi keselamatan hewan.</li>
  <li>Seluruh biaya tindakan medis darurat menjadi <strong>tanggung jawab pemilik</strong>.</li>
</ol>

<h4>3. Isolasi</h4>
<ol>
  <li>Hewan yang menunjukkan gejala sakit selama penitipan akan diisolasi dari hewan lain.</li>
  <li>Pemilik akan segera diberitahu dan diminta untuk mengambil hewan atau memberikan persetujuan tindakan medis.</li>
</ol>

<h4>4. Keadaan Kahar (Force Majeure)</h4>
<ol>
  <li>Radhiyan Pet and Care tidak bertanggung jawab atas kejadian yang disebabkan oleh kondisi di luar kendali manusia (bencana alam, wabah penyakit, dll.).</li>
  <li>Dalam kondisi tersebut, Radhiyan Pet and Care akan berupaya semaksimal mungkin melindungi hewan yang dititipkan.</li>
</ol>
HTML,
            ],

            [
                'title'   => 'Kebijakan Pembayaran & Pembatalan',
                'version' => '1.0',
                'status'  => 'active',
                'content' => <<<HTML
<h2>KEBIJAKAN PEMBAYARAN &amp; PEMBATALAN</h2>
<h3>Radhiyan Pet and Care</h3>

<h4>1. Ketentuan Pembayaran</h4>
<ol>
  <li>Pembayaran dapat dilakukan secara tunai, transfer bank, atau melalui dompet digital (QRIS, GoPay, OVO, dll.).</li>
  <li>Biaya penitipan dihitung berdasarkan <strong>jumlah hari menginap</strong> sesuai tarif yang berlaku.</li>
  <li>Pembayaran DP (Down Payment) dapat dilakukan di awal penitipan. Pelunasan dilakukan saat check-out.</li>
  <li>Biaya tambahan (grooming, obat-obatan, vaksinasi, dll.) akan ditagihkan terpisah sesuai layanan yang diberikan.</li>
</ol>

<h4>2. Pembatalan &amp; Refund</h4>
<ol>
  <li>Pembatalan yang dilakukan <strong>lebih dari 24 jam</strong> sebelum jadwal check-in: DP dapat dikembalikan sepenuhnya.</li>
  <li>Pembatalan yang dilakukan <strong>kurang dari 24 jam</strong> sebelum jadwal check-in: DP tidak dapat dikembalikan.</li>
  <li>Pemotongan masa penitipan (pulang lebih awal) tidak mempengaruhi biaya yang telah disepakati.</li>
</ol>

<h4>3. Keterlambatan Pembayaran</h4>
<ol>
  <li>Pelunasan wajib diselesaikan pada saat check-out.</li>
  <li>Keterlambatan pembayaran dapat dikenakan <strong>biaya keterlambatan</strong> sebesar Rp 50.000 per hari.</li>
</ol>
HTML,
            ],

            [
                'title'   => 'Persetujuan Tindakan Medis & Grooming',
                'version' => '1.0',
                'status'  => 'active',
                'content' => <<<HTML
<h2>PERSETUJUAN TINDAKAN MEDIS &amp; GROOMING</h2>
<h3>Radhiyan Pet and Care</h3>

<p>Dengan menandatangani dokumen ini, pemilik hewan peliharaan memberikan persetujuan kepada Radhiyan Pet and Care untuk:</p>

<h4>1. Tindakan Preventif Rutin</h4>
<ol>
  <li>Melakukan pemeriksaan fisik harian selama masa penitipan.</li>
  <li>Memberikan obat cacing atau obat kutu apabila diperlukan dan atas persetujuan pemilik.</li>
  <li>Melakukan grooming dasar (mandi, sisir bulu) sesuai paket yang dipilih.</li>
</ol>

<h4>2. Tindakan Medis Darurat</h4>
<ol>
  <li>Apabila hewan dalam kondisi darurat yang mengancam jiwa dan pemilik <strong>tidak dapat dihubungi</strong>, dokter hewan Radhiyan Pet and Care berhak melakukan tindakan medis yang diperlukan.</li>
  <li>Pemilik menyetujui bahwa biaya tindakan darurat tersebut akan menjadi tanggung jawab pemilik.</li>
  <li>Radhiyan Pet and Care akan mendokumentasikan seluruh tindakan medis yang diberikan.</li>
</ol>

<h4>3. Grooming &amp; Perawatan Penampilan</h4>
<ol>
  <li>Grooming dilakukan oleh groomer berpengalaman sesuai standar keamanan hewan.</li>
  <li>Radhiyan Pet and Care tidak bertanggung jawab atas perubahan penampilan yang merupakan hasil standar grooming (pemotongan bulu, dll.) yang telah dikomunikasikan sebelumnya.</li>
  <li>Apabila ditemukan kondisi kulit atau bulu yang memerlukan penanganan khusus, petugas akan menghubungi pemilik terlebih dahulu.</li>
</ol>
HTML,
            ],

            [
                'title'   => 'Kebijakan Risiko & Batasan Tanggung Jawab',
                'version' => '1.0',
                'status'  => 'active',
                'content' => <<<HTML
<h2>KEBIJAKAN RISIKO &amp; BATASAN TANGGUNG JAWAB</h2>
<h3>Radhiyan Pet and Care</h3>

<p>Pemilik memahami dan menyetujui ketentuan risiko berikut:</p>

<h4>1. Risiko Umum Penitipan</h4>
<ol>
  <li>Pemilik memahami bahwa penitipan hewan peliharaan membawa risiko inheren, termasuk namun tidak terbatas pada: stres, penularan penyakit antar hewan, cedera ringan akibat interaksi sosial.</li>
  <li>Radhiyan Pet and Care akan berupaya meminimalkan risiko tersebut dengan prosedur yang telah ditetapkan.</li>
</ol>

<h4>2. Batasan Tanggung Jawab</h4>
<ol>
  <li>Radhiyan Pet and Care <strong>tidak bertanggung jawab</strong> atas kematian atau cedera yang diakibatkan oleh:
    <ul>
      <li>Kondisi kesehatan yang sudah ada sebelum penitipan dan tidak diinformasikan oleh pemilik.</li>
      <li>Kondisi medis yang bersifat tiba-tiba dan tidak dapat diprediksi (serangan jantung, alergi berat, dll.).</li>
      <li>Keadaan kahar (bencana alam, kebakaran, dll.).</li>
    </ul>
  </li>
  <li>Klaim kompensasi harus diajukan secara tertulis dalam <strong>7 hari kerja</strong> setelah kejadian.</li>
</ol>

<h4>3. Hak Radhiyan Pet and Care</h4>
<ol>
  <li>Radhiyan Pet and Care berhak menolak atau memindahkan hewan yang berperilaku agresif dan membahayakan petugas atau hewan lain.</li>
  <li>Radhiyan Pet and Care berhak meminta pemilik mengambil hewan lebih awal apabila kondisi hewan membutuhkan perawatan khusus yang tidak dapat dipenuhi.</li>
</ol>

<h4>4. Persetujuan Akhir</h4>
<p>Dengan menandatangani dokumen ini, pemilik menyatakan:</p>
<ul>
  <li>Telah membaca dan memahami seluruh kebijakan yang berlaku di Radhiyan Pet and Care.</li>
  <li>Memberikan informasi yang benar dan akurat mengenai kondisi hewan peliharaannya.</li>
  <li>Menyetujui seluruh syarat, ketentuan, dan kebijakan yang berlaku.</li>
</ul>
HTML,
            ],
        ];

        foreach ($policies as $policy) {
            DB::table('contract_templates')->insert([
                'title'       => $policy['title'],
                'raw_content' => $policy['content'],
                'status'      => $policy['status'],
                'version'     => $policy['version'],
                'isDeleted'   => 0,
                'userId'      => $userId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $this->command->info('ContractTemplateSeeder: ' . count($policies) . ' policies seeded.');
    }
}
