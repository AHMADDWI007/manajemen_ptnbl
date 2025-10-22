<div class="row g-3">
  <div class="col-md-4">
    <label for="tanggal_input" class="form-label">Tanggal Input Harian</label>
    {{-- ID diubah agar konsisten dengan controller dan validasi --}}
    <input type="date" id="tanggal_input" name="tanggal_input" class="form-control" required>
  </div>

  <div class="col-md-4">
    <label for="bak_maturasi" class="form-label">Bak Maturasi</label>
    <select id="bak_maturasi" name="uraian" class="form-control" required>
      <option value="">-- Pilih Bak Maturasi --</option>
      @for($i=1;$i<=49;$i++)
        <option value="Di Bak Maturasi {{ $i }}">Di Bak Maturasi {{ $i }}</option>
      @endfor
    </select>
  </div>

  {{-- Input Jenis, Berat Truck, Berat Timbang, Netto Basah, K3, Netto Kering tetap sama --}}
  <div class="col-md-4">
    <label for="jenis" class="form-label">Jenis</label>
    <input type="text" name="jenis" id="jenis" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label for="berat_truck" class="form-label">Berat Truk (Kg)</label>
    <input type="number" step="0.01" id="berat_truck" name="berat_truck" class="form-control hitungOtomatis" required>
  </div>
  <div class="col-md-4">
    <label for="berat_timbang" class="form-label">Berat Timbang (Kg)</label>
    <input type="number" step="0.01" id="berat_timbang" name="berat_timbang" class="form-control hitungOtomatis" required>
  </div>
  <div class="col-md-4">
    <label for="netto_basah" class="form-label">Netto Basah (Kg)</label>
    <input type="number" step="0.01" id="netto_basah" name="netto_basah" class="form-control" readonly>
  </div>
  <div class="col-md-4">
    <label for="k3" class="form-label">K3 (%)</label>
    <input type="number" step="0.01" id="k3" name="k3" class="form-control hitungOtomatis" required>
  </div>
  <div class="col-md-4">
    <label for="netto_kering" class="form-label">Netto Kering (Kg)</label>
    <input type="number" step="0.01" id="netto_kering" name="netto_kering" class="form-control" readonly>
  </div>
  {{-- / Input Jenis, Berat Truck, etc --}}

  <div class="col-md-4">
    <label for="stok_awal" class="form-label">Stok Awal (Kg)</label>
    {{-- Dibuat readonly --}}
    <input type="number" step="0.01" id="stok_awal" name="stok_awal" class="form-control" readonly required>
  </div>

  <div class="col-md-4">
    <label for="masuk_hi" class="form-label">Masuk Hari Ini (Kg)</label>
    <input type="number" step="0.01" id="masuk_hi" name="masuk_hi" class="form-control hitungStok">
  </div>

  <div class="col-md-4">
    <label for="diolah" class="form-label">Diolah (Kg)</label>
    <input type="number" step="0.01" id="diolah" name="diolah" class="form-control hitungStok">
  </div>

  {{-- TAMBAHKAN Input Mutasi jika belum ada --}}
  <div class="col-md-4">
    <label for="mutasi" class="form-label">Mutasi (Kg)</label>
    <input type="number" step="0.01" id="mutasi" name="mutasi" class="form-control hitungStok">
  </div>
  {{-- / Input Mutasi --}}

  <div class="col-md-4">
    <label for="stok_akhir" class="form-label">Stok Akhir (Kg)</label>
    <input type="number" step="0.01" id="stok_akhir" name="stok_akhir" class="form-control" readonly>
  </div>

  <div class="col-md-4">
    <label for="umur" class="form-label">Umur (Hari)</label>
    {{-- Dibuat readonly --}}
    <input type="number" step="1" id="umur" name="umur" class="form-control" readonly required>
  </div>

  <div class="col-md-6">
    <label for="asal_bokar" class="form-label">Asal Bokar</label>
    <input type="text" id="asal_bokar" name="asal_bokar" class="form-control">
  </div>

  <div class="col-md-6">
    <label for="keterangan" class="form-label">Keterangan (Otomatis)</label>
    <input type="text" id="keterangan" name="keterangan" class="form-control" readonly>
  </div>

  {{-- tgl_masuk_hidden tidak diperlukan lagi di frontend --}}
  {{-- <input type="hidden" id="tgl_masuk_hidden" name="tgl_masuk_hidden"> --}}
</div>

{{-- Pastikan jQuery sudah dimuat sebelum script ini --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> {{-- Contoh memuat jQuery --}}

<script>
document.addEventListener('DOMContentLoaded', function() {

  // === Hitung Netto Basah & Netto Kering ===
  function hitungNetto() {
    let bt = parseFloat(document.getElementById('berat_truck').value) || 0;
    let bb = parseFloat(document.getElementById('berat_timbang').value) || 0;
    let k3 = parseFloat(document.getElementById('k3').value) || 0;
    let nettoBasah = bb - bt;
    let nettoKering = nettoBasah * (k3 / 100);
    document.getElementById('netto_basah').value = nettoBasah > 0 ? nettoBasah.toFixed(2) : '0.00';
    document.getElementById('netto_kering').value = nettoKering > 0 ? nettoKering.toFixed(2) : '0.00';
  }

  document.querySelectorAll('.hitungOtomatis').forEach(el => {
    el.addEventListener('input', hitungNetto);
  });

  // === Hitung Stok Akhir ===
  function hitungStok() {
    let awal = parseFloat(document.getElementById('stok_awal').value) || 0;
    let masuk = parseFloat(document.getElementById('masuk_hi').value) || 0;
    let diolah = parseFloat(document.getElementById('diolah').value) || 0;
    let mutasi = parseFloat(document.getElementById('mutasi').value) || 0; // Tambahkan mutasi
    let akhir = awal + masuk - diolah - mutasi; // Kurangi mutasi
    document.getElementById('stok_akhir').value = akhir.toFixed(2);
  }

  document.querySelectorAll('.hitungStok').forEach(el => {
    el.addEventListener('input', hitungStok);
  });

  // === Fungsi untuk memformat tanggal ke YYYY-MM-DD ===
  function formatDate(date) {
        let d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;

        return [year, month, day].join('-');
    }

  // === Fungsi untuk mengambil data hari sebelumnya via AJAX ===
  function fetchPreviousData() {
    let bak = document.getElementById('bak_maturasi').value;
    let tanggal = document.getElementById('tanggal_input').value; // Ambil tanggal dari input

    // Hanya jalankan jika bak maturasi DAN tanggal sudah dipilih
    if (bak && tanggal) {
       // Format tanggal ke YYYY-MM-DD jika belum
       let formattedDate = formatDate(tanggal);

       // Gunakan route yang sudah dibuat
       fetch(`{{ route('maturasi.getPreviousData') }}?uraian=${encodeURIComponent(bak)}&tanggal_input=${formattedDate}`)
         .then(response => {
           if (!response.ok) {
             throw new Error('Network response was not ok ' + response.statusText);
           }
           return response.json();
         })
         .then(data => {
           if (data) {
             document.getElementById('stok_awal').value = data.stok_awal !== null ? parseFloat(data.stok_awal).toFixed(2) : '0.00';
             document.getElementById('umur').value = data.umur !== null ? data.umur : 0;
             // tgl_masuk_hidden tidak diupdate lagi dari sini
           } else {
             // Jika API mengembalikan null (tidak ada data sebelumnya)
             document.getElementById('stok_awal').value = '0.00';
             document.getElementById('umur').value = 0;
             // tgl_masuk_hidden tidak diupdate lagi dari sini
           }
           // Hitung ulang stok akhir setelah stok awal diupdate
           hitungStok();
         })
         .catch(error => {
           console.error('Gagal mengambil data sebelumnya:', error);
           // Set default jika error
           document.getElementById('stok_awal').value = '0.00';
           document.getElementById('umur').value = 0;
           // tgl_masuk_hidden tidak diupdate lagi dari sini
           hitungStok(); // Hitung ulang stok akhir
         });
    } else {
        // Jika salah satu (bak atau tanggal) belum dipilih, reset
         document.getElementById('stok_awal').value = '0.00';
         document.getElementById('umur').value = 0;
         hitungStok();
    }
  }

  // === Fungsi untuk update Keterangan berdasarkan Tanggal ===
    function updateKeterangan() {
        let tanggalInput = document.getElementById('tanggal_input').value;
        let keteranganField = document.getElementById('keterangan');

        if (tanggalInput) {
            try {
                // Buat objek Date dan format ke Bahasa Indonesia
                let dateObj = new Date(tanggalInput + 'T00:00:00'); // Tambahkan T00:00:00 untuk hindari masalah timezone
                let options = { day: 'numeric', month: 'long' };
                keteranganField.value = dateObj.toLocaleDateString('id-ID', options);
            } catch (e) {
                console.error("Format tanggal tidak valid:", e);
                keteranganField.value = 'Tanggal Invalid';
            }
        } else {
            keteranganField.value = ''; // Kosongkan jika tidak ada tanggal
        }
    }


  // === Tambahkan event listener untuk Bak Maturasi dan Tanggal Input ===
  document.getElementById('bak_maturasi').addEventListener('change', fetchPreviousData);
  document.getElementById('tanggal_input').addEventListener('change', function() {
      fetchPreviousData(); // Ambil data stok & umur
      updateKeterangan(); // Update keterangan tanggal
  });

  // Panggil hitungNetto saat halaman dimuat jika ada nilai awal
  hitungNetto();
  // Panggil hitungStok saat halaman dimuat jika ada nilai awal
  hitungStok();
  // Panggil updateKeterangan saat halaman dimuat jika ada nilai awal
  updateKeterangan();

});
</script>