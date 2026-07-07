<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SopSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ─────────────────────────────────────────────────────────────────────
        // KOMODITAS
        // Menyimpan daftar tanaman yang bisa dibudidaya di app ini.
        // id diisi manual supaya mudah direferensikan di tabel sop.
        // ─────────────────────────────────────────────────────────────────────
        $komoditas = [
            ['id' => 1, 'nama_komoditas' => 'Timun Kyuri', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nama_komoditas' => 'Tomat',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'nama_komoditas' => 'Melon',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'nama_komoditas' => 'Labu',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'nama_komoditas' => 'Sawi',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'nama_komoditas' => 'Wortel',      'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($komoditas as $k) {
            DB::table('komoditas')->updateOrInsert(
                ['id' => $k['id']],
                $k
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // SOP (TAHAPAN BUDIDAYA)
        // Format tiap baris: [id, id_komoditas, nama_tahapan, estimasi_hari, deskripsi]
        //
        // id          = nomor unik tahapan ini di tabel sop
        // id_komoditas= komoditas mana yang punya tahapan ini (1=TimunKyuri, 2=Tomat, 3=Melon)
        // nama_tahapan= nama fase budidaya
        // estimasi_hari= perkiraan berapa hari fase ini berlangsung
        // deskripsi   = penjelasan singkat fase ini
        // ─────────────────────────────────────────────────────────────────────
        $sopData = [

            // ══ TIMUN KYURI (id_komoditas: 1) ════════════════════════════════
            [1,  1, 'Persiapan Lahan',   14, 'Persiapan lahan untuk budidaya Timun Kyuri meliputi pembersihan, pengolahan tanah, dan pembuatan bedengan. Lahan harus bersih, subur, dan memiliki drainase yang baik.'],
            [2,  1, 'Penyemaian',         7, 'Penyemaian benih Timun Kyuri menggunakan media tanam steril di tray semai sebelum dipindahkan ke lahan utama.'],
            [3,  1, 'Fase Vegetatif',    21, 'Fase pertumbuhan daun dan batang Timun Kyuri. Fokus pada pembentukan struktur tanaman yang kuat sebelum berbuah.'],
            [4,  1, 'Fase Generatif',    25, 'Fase pembungaan dan pembuahan Timun Kyuri. Pemeliharaan intensif untuk memaksimalkan jumlah dan kualitas buah.'],
            [5,  1, 'Pasca Panen',        7, 'Penanganan hasil panen agar kualitas terjaga. Meliputi sortasi, grading, pengemasan, dan distribusi.'],
            [6,  1, 'Evaluasi Budidaya',  3, 'Evaluasi menyeluruh proses budidaya dari awal hingga panen. Identifikasi kendala dan perbaikan untuk musim berikutnya.'],

            // ══ TOMAT (id_komoditas: 2) ═══════════════════════════════════════
            [7,  2, 'Persiapan Lahan',   14, 'Persiapan lahan tomat meliputi pengolahan tanah dalam, pemupukan dasar organik, dan pembuatan bedengan berdrainase baik.'],
            [8,  2, 'Penyemaian',        14, 'Tomat disemai selama 14 hari di tray semai sebelum dipindahtanamkan. Bibit siap tanam saat sudah berdaun 4-5 helai.'],
            [9,  2, 'Fase Vegetatif',    30, 'Fase pertumbuhan batang, daun, dan pembentukan cabang produksi tomat. Pemangkasan tunas air rutin sangat penting.'],
            [10, 2, 'Fase Generatif',    40, 'Fase pembungaan hingga pembuahan tomat. Pemangkasan tunas air dan pengendalian penyakit layu menjadi prioritas utama.'],
            [11, 2, 'Pasca Panen',        7, 'Panen tomat dilakukan bertahap sesuai tingkat kematangan. Sortasi dan grading menentukan nilai jual produk.'],
            [12, 2, 'Evaluasi Budidaya',  3, 'Evaluasi produktivitas, kualitas buah, dan efisiensi penggunaan input produksi selama satu musim tanam tomat.'],

            // ══ MELON (id_komoditas: 3) ═══════════════════════════════════════
            [13, 3, 'Persiapan Lahan',   21, 'Persiapan lahan melon membutuhkan waktu lebih lama karena tanaman melon sangat sensitif terhadap kondisi tanah dan drainase.'],
            [14, 3, 'Penyemaian',        10, 'Benih melon disemai di tray dengan media cocopeat steril. Perkecambahan terjadi dalam 3-5 hari pada suhu 28-32°C.'],
            [15, 3, 'Fase Vegetatif',    25, 'Fase vegetatif melon mencakup pembentukan sulur, pemangkasan cabang, dan pemasangan tali rambat atau ajir.'],
            [16, 3, 'Fase Generatif',    35, 'Fase terpenting melon. Penyerbukan manual sering dilakukan untuk memastikan pembentukan buah yang optimal dan seragam.'],
            [17, 3, 'Pasca Panen',        7, 'Melon dipanen pada tingkat kematangan tepat. Penanganan hati-hati menentukan kualitas dan harga jual di pasaran.'],
            [18, 3, 'Evaluasi Budidaya',  3, 'Evaluasi menyeluruh budidaya melon termasuk analisis biaya produksi versus pendapatan dan identifikasi kendala.'],

            // ══ LABU (id_komoditas: 4) ════════════════════════════════════════
            [19, 4, 'Persiapan Lahan',   14, 'Labu membutuhkan lahan dengan ruang cukup karena tanaman merambat. Buat lubang tanam besar dengan pupuk organik berlimpah.'],
            [20, 4, 'Penyemaian',         7, 'Benih labu besar sehingga mudah disemai langsung di polybag sebelum dipindah ke lahan. Media cocopeat dan tanah 1:1.'],
            [21, 4, 'Fase Vegetatif',    21, 'Labu tumbuh cepat dengan sulur panjang. Arahkan sulur ke ajir atau tali rambat. Pangkas tunas lateral agar tanaman fokus.'],
            [22, 4, 'Fase Generatif',    35, 'Bunga labu besar dan mencolok. Lakukan penyerbukan manual pagi hari untuk memastikan pembuahan optimal.'],
            [23, 4, 'Pasca Panen',        7, 'Labu dipanen saat kulit mengeras dan tangkai mengering. Simpan di tempat sejuk dan kering untuk memperpanjang umur simpan.'],
            [24, 4, 'Evaluasi Budidaya',  3, 'Evaluasi hasil panen labu meliputi berat rata-rata buah, jumlah buah per tanaman, dan efisiensi penggunaan lahan.'],
 
            // ══ SAWI (id_komoditas: 5) ════════════════════════════════════════
            [25, 5, 'Persiapan Lahan',    7, 'Sawi cocok di tanah gembur, subur, dan kaya bahan organik. Buat bedengan selebar 100-120 cm dengan drainase baik.'],
            [26, 5, 'Penyemaian',         7, 'Sawi bisa disemai langsung di bedengan atau di tray. Tabur benih tipis lalu tutup dengan tanah halus setebal 0.5 cm.'],
            [27, 5, 'Fase Vegetatif',    21, 'Sawi tumbuh cepat dalam 3 minggu. Siram rutin pagi dan sore, beri pupuk N tinggi untuk memacu pertumbuhan daun.'],
            [28, 5, 'Penjarangan',        3, 'Jarangkan tanaman sawi yang terlalu rapat agar masing-masing mendapat ruang tumbuh cukup. Jarak ideal 20x20 cm.'],
            [29, 5, 'Pasca Panen',        3, 'Sawi dipanen saat daun sudah penuh dan kompak, sekitar 25-35 hari setelah tanam. Panen pagi hari agar kesegaran optimal.'],
            [30, 5, 'Evaluasi Budidaya',  2, 'Evaluasi sawi meliputi berat total per bedengan, persentase susut, dan kecepatan pertumbuhan dibanding target.'],
 
            // ══ WORTEL (id_komoditas: 6) ══════════════════════════════════════
            [31, 6, 'Persiapan Lahan',   14, 'Wortel butuh tanah dalam, gembur, dan bebas batu agar umbi berkembang lurus. Gemburkan tanah hingga kedalaman 40-50 cm.'],
            [32, 6, 'Penyemaian',         7, 'Benih wortel sangat kecil. Campurkan dengan pasir halus agar penyebaran merata. Siram halus agar benih tidak terbawa air.'],
            [33, 6, 'Fase Vegetatif',    30, 'Pertumbuhan daun wortel membutuhkan nitrogen cukup. Jaga kelembaban tanah merata karena kekeringan menyebabkan umbi bercabang.'],
            [34, 6, 'Penjarangan & Penyiangan', 7, 'Jarangkan wortel jarak 5-8 cm saat tinggi 5 cm. Siangi gulma rutin agar tidak berebut nutrisi dengan tanaman.'],
            [35, 6, 'Pasca Panen',        7, 'Wortel dipanen 80-100 hari setelah tanam. Cabut dengan hati-hati, bersihkan tanah, potong daun, dan sortir berdasarkan ukuran.'],
            [36, 6, 'Evaluasi Budidaya',  3, 'Evaluasi wortel meliputi persentase umbi lurus vs bercabang, berat rata-rata umbi, dan analisis penyebab cacat produk.'],
        ];

        $sopInsert = [];
        foreach ($sopData as $s) {
            $sopInsert[] = [
                'id'            => $s[0],
                'id_komoditas'  => $s[1],
                'nama_tahapan'  => $s[2],
                'estimasi_hari' => $s[3],
                'deskripsi'     => $s[4],
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach ($sopInsert as $s) {
            DB::table('sop')->updateOrInsert(
                ['id' => $s['id']],
                $s
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // SOP LANGKAH (langkah detail tiap tahapan)
        // Format tiap baris: [id_sop, urutan, judul_langkah, deskripsi, hasil_diharapkan]
        //
        // id_sop          = tahapan mana yang punya langkah ini
        // urutan          = langkah ke berapa (1,2,3 dst)
        // judul_langkah   = nama singkat langkah, ini yang muncul di checklist
        // deskripsi       = cara melakukannya
        // hasil_diharapkan= target setelah langkah ini selesai
        // ─────────────────────────────────────────────────────────────────────
        $langkahData = [

            // ── Timun Kyuri: Persiapan Lahan (id_sop: 1) ─────────────────────
            [1, 1, 'Bersihkan lahan dari gulma dan sisa tanaman',
                'Cabut atau potong semua rumput liar, sisa akar, dan sampah organik dari lahan.',
                'Lahan bersih dan bebas dari gulma serta sisa tanaman lama'],
            [1, 2, 'Olah tanah sedalam 30-40 cm',
                'Bajak atau cangkul tanah hingga kedalaman 30-40 cm agar gembur dan aerasi baik.',
                'Tanah gembur, tidak padat, dan siap menerima pupuk dasar'],
            [1, 3, 'Berikan pupuk dasar kompos/kandang 20-30 ton/ha',
                'Sebarkan pupuk kompos atau kandang secara merata lalu aduk dengan tanah.',
                'Pupuk tercampur rata dan kandungan bahan organik meningkat'],
            [1, 4, 'Buat bedengan lebar 120 cm, tinggi 30 cm',
                'Bentuk bedengan dengan ukuran standar menggunakan cangkul atau mesin bedengan.',
                'Bedengan terbentuk rapi sesuai ukuran standar'],
            [1, 5, 'Pasang mulsa plastik hitam perak',
                'Pasang mulsa dengan sisi perak menghadap atas untuk memantulkan cahaya.',
                'Mulsa terpasang rapi menutup seluruh permukaan bedengan'],
            [1, 6, 'Buat lubang tanam jarak 50x60 cm',
                'Buat lubang di mulsa menggunakan alat pelubang dengan jarak yang ditentukan.',
                'Lubang tanam tersedia sesuai jarak tanam yang direncanakan'],

            // ── Timun Kyuri: Penyemaian (id_sop: 2) ──────────────────────────
            [2, 1, 'Rendam benih dalam air hangat 30°C selama 2-4 jam',
                'Rendam benih untuk memecah dormansi dan mempercepat perkecambahan.',
                'Benih mengembang dan siap disemai'],
            [2, 2, 'Siapkan tray semai dengan media cocopeat dan perlite (3:1)',
                'Campurkan media semai dengan perbandingan 3:1, pastikan steril dan drainase baik.',
                'Media semai siap dengan tekstur gembur dan tidak terlalu basah'],
            [2, 3, 'Tanam 1 benih per lubang sedalam 1-2 cm',
                'Tanam benih dengan posisi tegak, tutup tipis dengan media semai.',
                'Benih tertanam di semua lubang tray'],
            [2, 4, 'Siram halus dan tutup plastik transparan',
                'Siram merata dengan sprayer, tutup tray untuk menjaga kelembaban.',
                'Kelembaban terjaga dan tray tertutup rapat'],
            [2, 5, 'Pindahkan bibit saat berdaun 2-3 helai (±7 hari)',
                'Bibit siap dipindah saat memiliki 2-3 daun sejati dan tinggi sekitar 10 cm.',
                'Bibit sehat, kokoh, dan siap transplanting'],

            // ── Timun Kyuri: Fase Vegetatif (id_sop: 3) ──────────────────────
            [3, 1, 'Pasang ajir atau tali rambat setinggi 150-180 cm',
                'Pasang ajir bambu atau tali plastik untuk menopang tanaman yang merambat.',
                'Sistem rambatan terpasang kokoh'],
            [3, 2, 'Siram 1-2 kali sehari pagi dan sore',
                'Siram secara rutin, hindari genangan air di sekitar batang.',
                'Tanaman mendapat air cukup tanpa kelebihan'],
            [3, 3, 'Berikan pupuk NPK 16-16-16 dosis 2 gr/liter seminggu sekali',
                'Larutkan pupuk NPK dan siramkan ke area perakaran tanaman.',
                'Tanaman tumbuh subur dengan daun hijau segar'],
            [3, 4, 'Arahkan sulur ke ajir dan ikat longgar',
                'Ikat sulur ke ajir menggunakan tali rafia secara longgar agar tidak melukai batang.',
                'Tanaman merambat teratur ke atas'],
            [3, 5, 'Buang tunas air di ketiak daun (wiwilan)',
                'Pangkas tunas air untuk memfokuskan energi ke pertumbuhan utama.',
                'Tanaman memiliki satu batang utama yang kuat'],
            [3, 6, 'Monitor hama kutu daun, thrips, dan ulat',
                'Periksa tanaman setiap 2 hari, semprot pestisida jika ditemukan hama.',
                'Tidak ada serangan hama yang signifikan'],

            // ── Timun Kyuri: Fase Generatif (id_sop: 4) ──────────────────────
            [4, 1, 'Identifikasi bunga jantan dan betina',
                'Bunga betina memiliki bakal buah kecil di pangkalnya, bunga jantan tidak.',
                'Dapat membedakan bunga jantan dan betina dengan benar'],
            [4, 2, 'Lakukan penyerbukan manual jika lebah tidak tersedia',
                'Ambil serbuk sari dari bunga jantan dengan kuas kecil dan oleskan ke putik bunga betina.',
                'Bunga betina terserbuki dan bakal buah mulai berkembang'],
            [4, 3, 'Berikan pupuk kalium tinggi untuk pembesaran buah',
                'Gunakan pupuk KNO3 atau MKP untuk mendukung pengisian buah.',
                'Buah berkembang dengan ukuran optimal'],
            [4, 4, 'Pangkas daun tua dan daun terserang penyakit',
                'Buang daun yang menguning atau rusak untuk menjaga sirkulasi udara.',
                'Sirkulasi udara tanaman baik'],
            [4, 5, 'Panen buah ukuran 20-25 cm setiap 2-3 hari',
                'Panen timun kyuri saat ukuran 20-25 cm dengan warna hijau cerah.',
                'Panen dilakukan rutin setiap 2-3 hari'],

            // ── Timun Kyuri: Pasca Panen (id_sop: 5) ─────────────────────────
            [5, 1, 'Sortir hasil panen berdasarkan ukuran dan kualitas',
                'Pisahkan timun berdasarkan grade A (premium), B (reguler), dan afkir.',
                'Hasil panen tersortir rapi berdasarkan kualitas'],
            [5, 2, 'Cuci hasil panen dengan air bersih',
                'Cuci timun untuk menghilangkan kotoran dan sisa pestisida.',
                'Timun bersih dan aman dikonsumsi'],
            [5, 3, 'Kemas sesuai permintaan pasar',
                'Kemas dalam plastik wrap atau kardus sesuai standar pasar.',
                'Produk terkemas rapi dan siap distribusi'],
            [5, 4, 'Catat total hasil panen dan bandingkan dengan target',
                'Dokumentasikan total kg hasil panen dan bandingkan dengan estimasi awal.',
                'Data produksi tercatat lengkap'],

            // ── Timun Kyuri: Evaluasi (id_sop: 6) ────────────────────────────
            [6, 1, 'Buat laporan rekapitulasi seluruh kegiatan budidaya',
                'Kumpulkan semua catatan aktivitas, biaya input, dan hasil panen.',
                'Laporan rekapitulasi tersusun lengkap'],
            [6, 2, 'Analisis kendala selama budidaya',
                'Identifikasi masalah yang muncul dan catat solusi yang sudah diterapkan.',
                'Daftar kendala dan solusi terdokumentasi'],
            [6, 3, 'Hitung total keuntungan atau kerugian',
                'Hitung total pendapatan dikurangi total biaya produksi.',
                'Nilai keuntungan atau kerugian diketahui dengan jelas'],
            [6, 4, 'Buat rekomendasi untuk musim tanam berikutnya',
                'Tulis rekomendasi perbaikan berdasarkan pengalaman musim ini.',
                'Rekomendasi perbaikan tersusun untuk musim berikutnya'],

            // ── Tomat: Persiapan Lahan (id_sop: 7) ───────────────────────────
            [7, 1, 'Bersihkan lahan dari gulma dan sisa tanaman',
                'Bersihkan sisa tanaman lama dan gulma secara menyeluruh.',
                'Lahan bersih dan siap diolah'],
            [7, 2, 'Olah tanah sedalam 30-40 cm dan beri pupuk dasar',
                'Bajak tanah dan berikan pupuk kandang matang 25 ton/ha.',
                'Tanah subur, gembur, dan kaya bahan organik'],
            [7, 3, 'Buat bedengan lebar 100-120 cm',
                'Bentuk bedengan dengan drainase yang baik untuk menghindari genangan.',
                'Bedengan terbentuk rapi dengan drainase lancar'],
            [7, 4, 'Pasang mulsa plastik dan ajir sedini mungkin',
                'Pasang mulsa dan siapkan ajir sebelum tanam untuk efisiensi kerja.',
                'Mulsa dan ajir terpasang sebelum penanaman'],
            [7, 5, 'Cek pH tanah, idealnya 6.0-6.8',
                'Ukur pH tanah dan lakukan koreksi jika diperlukan.',
                'pH tanah berada di kisaran optimal'],

            // ── Tomat: Penyemaian (id_sop: 8) ────────────────────────────────
            [8, 1, 'Siapkan media semai campuran tanah, kompos, sekam (1:1:1)',
                'Campur dan sterilkan media semai sebelum digunakan.',
                'Media semai siap dan steril'],
            [8, 2, 'Tanam 1-2 benih per lubang tray sedalam 0.5-1 cm',
                'Tanam benih dan tutup tipis dengan media semai.',
                'Benih tertanam di semua lubang'],
            [8, 3, 'Jaga kelembaban dan letakkan di tempat teduh',
                'Siram halus setiap hari dan lindungi dari sinar matahari langsung.',
                'Benih berkecambah merata dalam 5-7 hari'],
            [8, 4, 'Pindahkan bibit saat tinggi 15 cm dan berdaun 4-5 helai',
                'Seleksi bibit yang sehat dan seragam untuk dipindahtanamkan.',
                'Bibit siap tanam di lahan utama'],

            // ── Tomat: Fase Vegetatif (id_sop: 9) ────────────────────────────
            [9, 1, 'Pasang ajir setinggi 150-200 cm per tanaman',
                'Pasang ajir bambu atau besi di samping setiap tanaman.',
                'Semua tanaman memiliki ajir pendukung'],
            [9, 2, 'Pangkas tunas air (wiwilan) setiap 3-4 hari',
                'Pangkas semua tunas di ketiak daun, sisakan 1-2 batang utama.',
                'Tanaman memiliki batang utama yang jelas'],
            [9, 3, 'Siram rutin dan berikan pupuk NPK seminggu sekali',
                'Siram setiap pagi dan beri pupuk NPK sesuai dosis anjuran.',
                'Pertumbuhan vegetatif optimal'],
            [9, 4, 'Ikat batang ke ajir setiap 2 minggu',
                'Ikat batang ke ajir secara berkala mengikuti pertumbuhan tanaman.',
                'Tanaman berdiri tegak dan tidak rebah'],
            [9, 5, 'Monitor hama ulat, kutu daun, dan penyakit bercak daun',
                'Periksa rutin dan lakukan pengendalian hama penyakit.',
                'Tanaman bebas dari serangan hama dan penyakit'],

            // ── Tomat: Fase Generatif (id_sop: 10) ───────────────────────────
            [10, 1, 'Pangkas tunas air dan daun tua secara rutin',
                'Lanjutkan pemangkasan tunas air dan buang daun tua di bagian bawah.',
                'Tanaman fokus pada produksi buah'],
            [10, 2, 'Lakukan penyerbukan dengan menggetarkan bunga setiap pagi',
                'Ketuk atau getarkan tangkai bunga untuk membantu pelepasan serbuk sari.',
                'Pembentukan buah berlangsung optimal'],
            [10, 3, 'Berikan pupuk kalium tinggi saat buah sebesar kelereng',
                'Aplikasikan pupuk MKP atau KNO3 untuk mendukung pembesaran buah.',
                'Buah berkembang optimal dan warna merata'],
            [10, 4, 'Monitor penyakit layu fusarium dan busuk buah',
                'Periksa harian dan semprot fungisida preventif seminggu sekali.',
                'Tidak ada serangan penyakit yang merusak'],
            [10, 5, 'Panen bertahap saat buah sudah berwarna merah 70-80%',
                'Panen tomat sebelum matang penuh untuk memperpanjang masa simpan.',
                'Panen berjalan lancar dan bertahap'],

            // ── Tomat: Pasca Panen (id_sop: 11) ──────────────────────────────
            [11, 1, 'Sortir tomat berdasarkan ukuran dan tingkat kematangan',
                'Pisahkan tomat grade A, B, dan afkir berdasarkan ukuran dan kualitas.',
                'Tomat tersortir rapi berdasarkan grade'],
            [11, 2, 'Simpan di tempat sejuk dan berventilasi',
                'Simpan tomat di suhu 12-15°C untuk memperpanjang masa simpan.',
                'Tomat tersimpan dalam kondisi baik'],
            [11, 3, 'Catat total hasil panen dan hitung produktivitas',
                'Dokumentasikan total produksi dan bandingkan dengan target.',
                'Data produksi tercatat lengkap'],

            // ── Tomat: Evaluasi (id_sop: 12) ─────────────────────────────────
            [12, 1, 'Rekap semua catatan aktivitas dan biaya',
                'Kumpulkan semua data kegiatan dan pengeluaran selama satu musim.',
                'Rekap data tersusun lengkap'],
            [12, 2, 'Analisis serangan hama penyakit dan cara pengendaliannya',
                'Evaluasi efektivitas metode pengendalian hama yang digunakan.',
                'Laporan analisis hama penyakit selesai'],
            [12, 3, 'Hitung keuntungan dan buat rekomendasi',
                'Hitung laba rugi dan buat rekomendasi untuk musim berikutnya.',
                'Laporan evaluasi lengkap tersusun'],

            // ── Melon: Persiapan Lahan (id_sop: 13) ──────────────────────────
            [13, 1, 'Bersihkan dan sanitasi lahan dengan kapur pertanian',
                'Bersihkan sisa tanaman dan taburkan kapur pertanian untuk desinfeksi.',
                'Lahan bersih dan bebas patogen'],
            [13, 2, 'Olah tanah sedalam 40 cm dan beri pupuk dasar 30 ton/ha',
                'Bajak tanah dalam-dalam dan berikan pupuk kandang matang.',
                'Tanah subur dan gembur'],
            [13, 3, 'Buat bedengan lebar 110 cm dengan jarak antar bedengan 70 cm',
                'Bentuk bedengan melon dengan ukuran standar dan pastikan drainase baik.',
                'Bedengan terbentuk dengan drainase yang baik'],
            [13, 4, 'Pasang mulsa plastik dan sistem irigasi tetes',
                'Pasang mulsa dan selang irigasi tetes sebelum tanam.',
                'Sistem irigasi terpasang dan berfungsi'],
            [13, 5, 'Cek pH tanah, idealnya 6.0-6.8',
                'Ukur pH tanah dan lakukan koreksi dengan kapur atau sulfur.',
                'pH tanah berada di kisaran optimal 6.0-6.8'],

            // ── Melon: Penyemaian (id_sop: 14) ───────────────────────────────
            [14, 1, 'Rendam benih melon dalam air hangat 2 jam',
                'Rendam untuk memecah dormansi dan memastikan benih berkualitas baik.',
                'Benih siap disemai'],
            [14, 2, 'Semai di tray dengan media cocopeat steril',
                'Isi tray semai dengan cocopeat, tanam 1 benih per lubang.',
                'Benih tertanam di semua lubang tray'],
            [14, 3, 'Jaga suhu persemaian 28-32°C',
                'Letakkan tray di tempat hangat atau gunakan greenhouse mini untuk menjaga suhu.',
                'Benih berkecambah dalam 3-5 hari'],
            [14, 4, 'Pindahkan bibit saat berdaun 2 helai (±10 hari)',
                'Bibit siap pindah saat memiliki 2 daun sejati yang membuka sempurna.',
                'Bibit sehat siap dipindahtanamkan'],

            // ── Melon: Fase Vegetatif (id_sop: 15) ───────────────────────────
            [15, 1, 'Pasang tali rambat vertikal setinggi 2 meter',
                'Pasang tali dari bawah ke atas untuk menopang tanaman melon merambat.',
                'Sistem rambatan vertikal terpasang kokoh'],
            [15, 2, 'Pangkas cabang lateral, sisakan cabang utama saja',
                'Potong semua cabang samping untuk memfokuskan pertumbuhan ke batang utama.',
                'Tanaman memiliki satu batang utama yang kuat'],
            [15, 3, 'Siram setiap hari via irigasi tetes',
                'Pastikan irigasi tetes berjalan lancar dan merata ke semua tanaman.',
                'Tanaman mendapat air cukup dan merata'],
            [15, 4, 'Beri pupuk NPK seminggu sekali',
                'Berikan pupuk NPK seimbang untuk mendukung pertumbuhan vegetatif.',
                'Pertumbuhan daun dan batang optimal'],
            [15, 5, 'Monitor hama aphid, thrips, dan penyakit embun tepung',
                'Periksa rutin dan semprot pestisida atau fungisida jika diperlukan.',
                'Tanaman bebas hama dan penyakit'],

            // ── Melon: Fase Generatif (id_sop: 16) ───────────────────────────
            [16, 1, 'Biarkan bunga muncul di ruas ke-8 hingga ke-12',
                'Jangan pangkas bunga yang muncul di ruas ke-8 sampai ke-12.',
                'Bunga muncul di posisi yang tepat'],
            [16, 2, 'Lakukan penyerbukan manual setiap pagi pukul 06.00-09.00',
                'Ambil serbuk sari bunga jantan dan oleskan ke putik bunga betina menggunakan kuas.',
                'Penyerbukan berhasil dan bakal buah terbentuk'],
            [16, 3, 'Seleksi buah, sisakan 1-2 buah per tanaman',
                'Pilih buah yang terbentuk paling sempurna, buang sisanya agar nutrisi terfokus.',
                'Setiap tanaman memiliki 1-2 buah berkualitas'],
            [16, 4, 'Jaring buah menggunakan net melon saat sebesar kepalan tangan',
                'Pasang jaring net di bawah buah untuk menopang berat buah.',
                'Semua buah terjaring dan tidak jatuh'],
            [16, 5, 'Berikan pupuk kalium tinggi untuk pengisian dan penggulaan buah',
                'Aplikasikan pupuk KNO3 atau MKP untuk meningkatkan kadar gula buah.',
                'Buah manis dengan brix di atas 12%'],
            [16, 6, 'Kurangi penyiraman 7-10 hari sebelum panen',
                'Kurangi air agar kadar gula meningkat dan buah tidak retak.',
                'Kadar gula buah optimal dan kulit tidak retak'],

            // ── Melon: Pasca Panen (id_sop: 17) ──────────────────────────────
            [17, 1, 'Cek kematangan dengan menekan pangkal buah dan cium aromanya',
                'Buah matang terasa sedikit lunak di pangkal dan beraroma harum.',
                'Waktu panen tepat dan buah berkualitas premium'],
            [17, 2, 'Panen dengan memotong tangkai menyisakan 3-5 cm',
                'Potong tangkai buah menggunakan pisau steril dan sisakan sebagian tangkai.',
                'Buah terpanen tanpa merusak kualitas'],
            [17, 3, 'Sortir berdasarkan berat dan kualitas kulit',
                'Pisahkan melon grade A (>1.5 kg, kulit mulus) dan grade B.',
                'Melon tersortir rapi berdasarkan kualitas'],
            [17, 4, 'Simpan di suhu 10-15°C untuk memperpanjang masa simpan',
                'Simpan melon di ruang pendingin atau tempat sejuk berventilasi baik.',
                'Melon tersimpan dalam kondisi baik'],

            // ── Melon: Evaluasi (id_sop: 18) ─────────────────────────────────
            [18, 1, 'Ukur kadar gula (brix) dari sampel buah yang dipanen',
                'Gunakan refraktometer untuk mengukur kadar gula buah.',
                'Data kualitas buah terdokumentasi'],
            [18, 2, 'Rekap total produksi dan bandingkan dengan target',
                'Hitung total kg panen dan bandingkan dengan estimasi awal musim.',
                'Data produksi aktual vs target diketahui'],
            [18, 3, 'Analisis kendala dan buat rekomendasi perbaikan',
                'Identifikasi masalah utama dan tulis solusi untuk musim berikutnya.',
                'Laporan evaluasi lengkap dan rekomendasi tersusun'],
 
            // ── Labu: Persiapan Lahan (id_sop: 19) ───────────────────────────
            [19, 1, 'Pilih lokasi dengan sinar matahari penuh', 'Labu membutuhkan sinar matahari minimal 6-8 jam per hari.', 'Lokasi optimal tersedia'],
            [19, 2, 'Gali lubang tanam 60x60x40 cm', 'Buat lubang besar dan isi dengan campuran tanah dan kompos 1:1.', 'Lubang tanam siap dengan media kaya organik'],
            [19, 3, 'Beri jarak antar tanaman minimal 2-3 meter', 'Labu merambat luas, tandai posisi setiap tanaman dengan patok.', 'Jarak tanam optimal tersedia untuk pertumbuhan sulur'],
            [19, 4, 'Siapkan sistem rambatan atau biarkan merambat di tanah', 'Tentukan apakah akan menggunakan ajir, trellis, atau biarkan merambat di tanah.', 'Sistem rambatan siap atau area merambat cukup'],
            [19, 5, 'Periksa drainase lahan', 'Pastikan tidak ada genangan air karena labu sensitif terhadap busuk akar.', 'Drainase berfungsi baik dan tidak ada genangan'],
 
            // ── Labu: Penyemaian (id_sop: 20) ────────────────────────────────
            [20, 1, 'Rendam benih labu 6-12 jam', 'Rendam benih besar labu dalam air hangat semalam untuk mempercepat perkecambahan.', 'Benih mengembang dan siap disemai'],
            [20, 2, 'Semai di polybag 15x20 cm media tanah:kompos (1:1)', 'Tanam 1-2 benih per polybag kedalaman 3-4 cm.', 'Benih tertanam di polybag'],
            [20, 3, 'Letakkan di tempat teduh parsial 3 hari pertama', 'Jangan langsung terkena sinar matahari penuh saat baru berkecambah.', 'Benih berkecambah tanpa stres sinar berlebih'],
            [20, 4, 'Pindahkan ke lokasi cahaya penuh setelah 3 hari', 'Pindahkan polybag ke tempat dengan sinar matahari penuh.', 'Bibit tumbuh kuat dan siap pindah lahan 7-10 hari'],
 
            // ── Labu: Fase Vegetatif (id_sop: 21) ────────────────────────────
            [21, 1, 'Tanam bibit saat sudah berdaun 3-4 helai', 'Pindahkan bibit dari polybag dengan hati-hati agar akar tidak rusak.', 'Bibit tertanam di lubang yang sudah disiapkan'],
            [21, 2, 'Arahkan sulur ke sistem rambatan', 'Bantu arah tumbuh sulur pertama ke ajir atau trellis yang sudah disiapkan.', 'Sulur mulai merambat ke arah yang diinginkan'],
            [21, 3, 'Siram 2 kali sehari saat cuaca panas', 'Labu butuh air banyak terutama saat pertumbuhan sulur aktif.', 'Tanaman terhidrasi dengan baik'],
            [21, 4, 'Pupuk NPK setiap 2 minggu', 'Berikan pupuk NPK 15-15-15 untuk mendukung pertumbuhan sulur dan daun.', 'Sulur tumbuh aktif dan daun hijau segar'],
            [21, 5, 'Pangkas ujung sulur jika terlalu panjang', 'Batasi panjang sulur agar energi terfokus ke pembentukan bunga dan buah.', 'Tanaman terkontrol dan tidak menjalar terlalu jauh'],
 
            // ── Labu: Fase Generatif (id_sop: 22) ────────────────────────────
            [22, 1, 'Identifikasi bunga jantan dan betina', 'Bunga betina memiliki bakal buah kecil di pangkalnya, bunga jantan tidak.', 'Bunga jantan dan betina teridentifikasi'],
            [22, 2, 'Lakukan penyerbukan manual pagi hari', 'Ambil bunga jantan yang baru mekar dan gosokkan ke putik bunga betina.', 'Penyerbukan berhasil ditandai bakal buah membesar'],
            [22, 3, 'Beri penyangga di bawah buah yang terbentuk', 'Letakkan kayu atau batu bata di bawah buah agar tidak menyentuh tanah lembab.', 'Buah tidak busuk karena kontak dengan tanah'],
            [22, 4, 'Tingkatkan pupuk K saat buah mulai membesar', 'Tambahkan pupuk KCl atau KNO3 untuk mempercepat pengisian buah.', 'Buah berkembang cepat dan padat'],
            [22, 5, 'Monitor hama lalat buah', 'Pasang perangkap atau semprot insektisida jika ada serangan lalat buah.', 'Buah bebas dari lalat buah'],
 
            // ── Labu: Pasca Panen (id_sop: 23) ───────────────────────────────
            [23, 1, 'Panen saat kulit mengeras dan tangkai mengering', 'Labu siap panen saat tangkai sudah mengering dan kulit tidak bisa ditusuk kuku.', 'Labu dipanen di waktu yang tepat'],
            [23, 2, 'Potong tangkai menyisakan 5 cm', 'Gunakan pisau tajam dan sisakan tangkai panjang agar buah lebih awet.', 'Labu terpanen dengan tangkai masih ada'],
            [23, 3, 'Jemur di sinar matahari 1-2 hari', 'Penjemuran singkat mengeraskan kulit dan memperpanjang masa simpan.', 'Kulit labu mengeras sempurna'],
            [23, 4, 'Simpan di tempat sejuk dan kering hingga 3-6 bulan', 'Labu bisa disimpan lama jika kulit utuh dan tempat penyimpanan kering.', 'Labu tersimpan tahan lama'],
 
            // ── Labu: Evaluasi (id_sop: 24) ───────────────────────────────────
            [24, 1, 'Hitung jumlah dan berat total buah per tanaman', 'Rekap hasil panen setiap tanaman dan hitung rata-ratanya.', 'Data produktivitas per tanaman tersedia'],
            [24, 2, 'Evaluasi keberhasilan penyerbukan', 'Hitung persentase bunga yang menjadi buah versus yang gugur.', 'Tingkat keberhasilan penyerbukan diketahui'],
            [24, 3, 'Buat rekomendasi untuk musim berikutnya', 'Identifikasi faktor yang meningkatkan atau menurunkan produksi.', 'Rekomendasi perbaikan tersusun'],
 
            // ── Sawi: Persiapan Lahan (id_sop: 25) ───────────────────────────
            [25, 1, 'Olah tanah sedalam 20-30 cm', 'Cangkul atau bajak ringan karena akar sawi tidak dalam.', 'Tanah gembur dan siap ditanami'],
            [25, 2, 'Tambahkan pupuk kompos 10-15 ton/ha', 'Sebarkan kompos dan aduk merata untuk meningkatkan kesuburan.', 'Tanah subur dengan bahan organik cukup'],
            [25, 3, 'Buat bedengan lebar 100-120 cm tinggi 20-25 cm', 'Bedengan sawi tidak perlu terlalu tinggi, cukup untuk drainase baik.', 'Bedengan terbentuk rapi'],
            [25, 4, 'Pastikan sumber air irigasi tersedia', 'Sawi butuh air rutin, pastikan akses irigasi mudah.', 'Sumber air tersedia dan dapat diakses dengan mudah'],
 
            // ── Sawi: Penyemaian (id_sop: 26) ────────────────────────────────
            [26, 1, 'Campurkan benih dengan pasir halus (1:3)', 'Pencampuran dengan pasir membantu distribusi benih yang lebih merata.', 'Benih siap disebar merata'],
            [26, 2, 'Taburkan benih tipis di atas bedengan', 'Sebar campuran benih-pasir secara merata di permukaan bedengan.', 'Benih tersebar merata di seluruh bedengan'],
            [26, 3, 'Tutup tipis dengan tanah halus 0.5 cm', 'Taburi benih dengan lapisan tanah tipis untuk melindungi dari hujan.', 'Benih tertutup tipis dan tidak hanyut air'],
            [26, 4, 'Siram halus menggunakan gembor berlubang kecil', 'Gunakan gembor dengan lubang halus agar benih tidak terbawa air.', 'Benih tetap di posisinya dan mendapat air cukup'],
            [26, 5, 'Pasang naungan paranet 50% selama 3-5 hari', 'Lindungi benih dari sinar langsung dan hujan lebat selama perkecambahan.', 'Benih berkecambah merata dalam 3-5 hari'],
 
            // ── Sawi: Fase Vegetatif (id_sop: 27) ────────────────────────────
            [27, 1, 'Siram pagi dan sore setiap hari', 'Jaga kelembaban tanah konsisten agar sawi tumbuh cepat dan seragam.', 'Tanah lembab dan sawi tumbuh cepat'],
            [27, 2, 'Berikan pupuk urea 2 minggu setelah tanam', 'Semprot atau siramkan larutan urea untuk memacu pertumbuhan daun.', 'Daun sawi hijau segar dan tumbuh cepat'],
            [27, 3, 'Kendalikan ulat daun dan kutu aphid', 'Periksa bagian bawah daun dan semprot insektisida bio jika ada hama.', 'Tanaman bebas hama pemakan daun'],
            [27, 4, 'Beri pupuk susulan seminggu sebelum panen', 'Aplikasikan pupuk NPK cair untuk meningkatkan bobot daun.', 'Bobot sawi meningkat menjelang panen'],
 
            // ── Sawi: Penjarangan (id_sop: 28) ────────────────────────────────
            [28, 1, 'Jarangkan saat tinggi 5-7 cm', 'Cabut tanaman yang terlalu rapat, sisakan jarak 20x20 cm.', 'Jarak antar tanaman optimal 20x20 cm'],
            [28, 2, 'Gunakan hasil penjarangan sebagai produk', 'Sawi yang dicabut saat penjarangan bisa dijual sebagai sawi baby.', 'Sawi baby terkumpul sebagai produk tambahan'],
 
            // ── Sawi: Pasca Panen (id_sop: 29) ───────────────────────────────
            [29, 1, 'Panen 25-35 hari setelah tanam', 'Panen sawi saat daun sudah penuh, kompak, dan belum berbunga.', 'Sawi dipanen di waktu yang tepat'],
            [29, 2, 'Cabut seluruh tanaman beserta akarnya', 'Cabut sawi dari tanah dan ikat per ikat sesuai ukuran pasar.', 'Sawi terikat rapi siap jual'],
            [29, 3, 'Cuci bersih dan tiriskan', 'Cuci sawi dengan air bersih untuk menghilangkan tanah dan kotoran.', 'Sawi bersih dan segar siap distribusi'],
            [29, 4, 'Simpan di tempat sejuk atau kulkas dalam 1-2 hari', 'Sawi mudah layu, segera distribusikan atau simpan di tempat dingin.', 'Kesegaran sawi terjaga'],
 
            // ── Sawi: Evaluasi (id_sop: 30) ───────────────────────────────────
            [30, 1, 'Timbang total produksi per bedengan', 'Rekap berat total panen dan hitung produktivitas per m².', 'Data produktivitas per m² tersedia'],
            [30, 2, 'Hitung persentase susut dan kerusakan', 'Rekap sawi yang tidak layak jual dan analisis penyebabnya.', 'Tingkat susut dan kerusakan diketahui'],
            [30, 3, 'Evaluasi kecepatan tumbuh vs target', 'Bandingkan waktu panen aktual dengan estimasi 25-35 hari.', 'Laporan evaluasi sawi tersusun'],
 
            // ── Wortel: Persiapan Lahan (id_sop: 31) ─────────────────────────
            [31, 1, 'Gemburkan tanah sedalam 40-50 cm', 'Gunakan cangkul dalam atau bajak singkal untuk menggemburkan tanah hingga dalam.', 'Tanah gembur dalam sehingga umbi bisa berkembang lurus'],
            [31, 2, 'Singkirkan batu, kerikil, dan akar kasar', 'Bersihkan semua benda keras yang menghalangi pertumbuhan umbi lurus.', 'Lahan bersih dari benda keras'],
            [31, 3, 'Tambahkan pasir kasar jika tanah terlalu liat', 'Campurkan pasir 20-30% untuk memperbaiki tekstur dan drainase.', 'Tekstur tanah sandy loam ideal untuk wortel'],
            [31, 4, 'Beri pupuk dasar fosfor tinggi (SP-36)', 'Wortel butuh P tinggi untuk perkembangan umbi yang baik.', 'Kadar fosfor tanah optimal untuk wortel'],
            [31, 5, 'Buat bedengan tinggi 30 cm lebar 100 cm', 'Bedengan tinggi memudahkan panen dan mencegah umbi membengkok.', 'Bedengan siap untuk penanaman wortel'],
 
            // ── Wortel: Penyemaian (id_sop: 32) ──────────────────────────────
            [32, 1, 'Campurkan benih wortel dengan pasir halus (1:5)', 'Benih wortel sangat kecil, pencampuran dengan pasir membantu distribusi merata.', 'Benih siap disebar merata'],
            [32, 2, 'Buat alur tanam sedalam 1-2 cm jarak 20 cm', 'Buat alur memanjang di bedengan sebagai jalur penebaran benih.', 'Alur tanam tersedia sesuai jarak yang ditentukan'],
            [32, 3, 'Taburkan campuran benih-pasir di alur', 'Sebar tipis dan merata sepanjang alur tanam.', 'Benih tersebar merata di alur tanam'],
            [32, 4, 'Tutup dengan tanah halus setebal 0.5-1 cm', 'Tutup benih dengan lapisan tanah tipis agar tidak hanyut air hujan.', 'Benih terlindungi dan siap berkecambah'],
            [32, 5, 'Siram halus setiap hari sampai berkecambah', 'Jaga kelembaban tanah agar benih berkecambah dalam 10-14 hari.', 'Benih berkecambah merata dalam 10-14 hari'],
 
            // ── Wortel: Fase Vegetatif (id_sop: 33) ──────────────────────────
            [33, 1, 'Siram secara konsisten agar tanah selalu lembab', 'Ketidakkonsistenan penyiraman menyebabkan umbi bercabang atau retak.', 'Kelembaban tanah konsisten sepanjang musim tanam'],
            [33, 2, 'Berikan pupuk N tinggi 3 minggu setelah tanam', 'Pupuk urea atau ZA mendukung pertumbuhan daun yang penting untuk fotosintesis.', 'Daun wortel lebat untuk fotosintesis optimal'],
            [33, 3, 'Timbun pangkal tanaman yang mulai terlihat', 'Tutup pangkal umbi yang terekspos cahaya agar tidak menghijau.', 'Umbi tumbuh lurus dan tidak menghijau di pangkalnya'],
            [33, 4, 'Monitor ulat tanah dan nematoda', 'Periksa adanya gejala serangan hama tanah yang merusak umbi.', 'Umbi bebas dari kerusakan hama tanah'],
 
            // ── Wortel: Penjarangan & Penyiangan (id_sop: 34) ────────────────
            [34, 1, 'Jarangkan tanaman jarak 5-8 cm saat tinggi 5 cm', 'Cabut tanaman berlebih agar setiap wortel punya ruang berkembang.', 'Jarak antar wortel optimal 5-8 cm'],
            [34, 2, 'Siangi gulma secara rutin setiap minggu', 'Cabut rumput liar dengan tangan atau kored kecil agar tidak berebut nutrisi.', 'Bedengan bersih dari gulma kompetitor'],
            [34, 3, 'Gemburkan permukaan tanah ringan setelah penjarangan', 'Aerasi ringan permukaan tanah membantu perkembangan umbi.', 'Tanah permukaan gembur dan beraerasi baik'],
 
            // ── Wortel: Pasca Panen (id_sop: 35) ─────────────────────────────
            [35, 1, 'Panen 80-100 hari setelah tanam', 'Wortel siap panen saat diameter umbi 2-3 cm dan warna oranye merata.', 'Wortel dipanen di waktu optimal'],
            [35, 2, 'Siram lahan sehari sebelum panen', 'Tanah yang lembab memudahkan pencabutan tanpa merusak umbi.', 'Umbi mudah dicabut tanpa kerusakan'],
            [35, 3, 'Cabut dengan tangan sambil pegang pangkal daun', 'Tarik perlahan dan lurus ke atas agar umbi tidak patah.', 'Umbi tercabut utuh dan tidak patah'],
            [35, 4, 'Potong daun menyisakan 2-3 cm dari pangkal umbi', 'Gunakan pisau bersih untuk memotong daun.', 'Umbi siap disortir dan dikemas'],
            [35, 5, 'Sortir berdasarkan ukuran dan kemulusan', 'Pisahkan grade A (lurus, >150g) grade B (sedikit bercabang) grade C (cacat).', 'Wortel tersortir siap untuk pasar yang berbeda'],
 
            // ── Wortel: Evaluasi (id_sop: 36) ─────────────────────────────────
            [36, 1, 'Hitung persentase umbi lurus vs bercabang', 'Rekap kualitas fisik umbi sebagai indikator keberhasilan persiapan lahan.', 'Data kualitas fisik umbi tersedia'],
            [36, 2, 'Rekap berat rata-rata umbi per tanaman', 'Timbang sampel wortel dan hitung rata-rata berat per umbi.', 'Data produktivitas per tanaman diketahui'],
            [36, 3, 'Analisis penyebab cacat produk', 'Identifikasi apakah cacat disebabkan tanah, air, hama, atau faktor lain.', 'Penyebab cacat diidentifikasi untuk perbaikan'],
            [36, 4, 'Buat rekomendasi persiapan lahan musim berikutnya', 'Tulis rekomendasi konkret berdasarkan temuan evaluasi.', 'Rekomendasi perbaikan tersusun'],
        ];

        $langkahInsert = [];
        $langkahId = 1;
        foreach ($langkahData as $l) {
            $langkahInsert[] = [
                'id'               => $langkahId++,
                'id_sop'           => $l[0],
                'urutan'           => $l[1],
                'judul_langkah'    => $l[2],
                'deskripsi'        => $l[3],
                'hasil_diharapkan' => $l[4],
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        foreach ($langkahInsert as $l) {
            DB::table('sop_langkah')->updateOrInsert(
                ['id' => $l['id']],
                $l
            );
        }

        // Tampilkan ringkasan hasil seeder
        $this->command->info('');
        $this->command->info('SopSeeder berhasil dijalankan!');
        $this->command->info('Komoditas   : ' . count($komoditas) . ' data (Timun Kyuri, Tomat, Melon, Labu, Sawi, Wortel)');
        $this->command->info('SOP         : ' . count($sopInsert) . ' tahapan (6 tahapan per komoditas)');
        $this->command->info('SOP Langkah : ' . count($langkahInsert) . ' langkah detail');
        $this->command->info('');
    }
}