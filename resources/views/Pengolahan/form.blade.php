<div class="row g-3">
  <div class="col-md-4">
    <label for="tanggal" class="form-label">Tanggal</label>
    <input type="date" id="tanggal" name="tanggal" class="form-control" required>
  </div>

  <div class="col-md-4">
    <label for="bak_maturasi" class="form-label">Bak Maturasi</label>
    <select id="bak_maturasi" name="bak_maturasi" class="form-control" required>
      <option value="">-- Pilih Bak Maturasi --</option>
      @for($i=1;$i<=49;$i++)
        <option value="Di Bak Maturasi {{ $i }}">Di Bak Maturasi {{ $i }}</option>
      @endfor
    </select>
  </div>

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

  <div class="col-md-4">
    <label for="stok_awal" class="form-label">Stok Awal (Kg)</label>
    <input type="number" step="0.01" id="stok_awal" name="stok_awal" class="form-control" readonly>
  </div>

  <div class="col-md-4">
    <label for="masuk_hi" class="form-label">Masuk Hari Ini (Kg)</label>
    <input type="number" step="0.01" id="masuk_hi" name="masuk_hi" class="form-control hitungStok">
  </div>

  <div class="col-md-4">
    <label for="diolah" class="form-label">Diolah (Kg)</label>
    <input type="number" step="0.01" id="diolah" name="diolah" class="form-control hitungStok">
  </div>

  <div class="col-md-4">
    <label for="stok_akhir" class="form-label">Stok Akhir (Kg)</label>
    <input type="number" step="0.01" id="stok_akhir" name="stok_akhir" class="form-control" readonly>
  </div>

  <div class="col-md-4">
    <label for="umur" class="form-label">Umur (Hari)</label>
    <input type="number" step="1" id="umur" name="umur" class="form-control" readonly>
  </div>

  <div class="col-md-6">
    <label for="asal_bokar" class="form-label">Asal Bokar</label>
    <input type="text" id="asal_bokar" name="asal_bokar" class="form-control">
  </div>

  <div class="col-md-6">
    <label for="keterangan" class="form-label">Keterangan</label>
    <input type="text" id="keterangan" name="keterangan" class="form-control">
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

  // === Hitung Netto Basah & Netto Kering ===
  function hitungNetto() {
    let bt = parseFloat(document.getElementById('berat_truck').value) || 0;
    let bb = parseFloat(document.getElementById('berat_timbang').value) || 0;
    let k3 = parseFloat(document.getElementById('k3').value) || 0;
    let nettoBasah = bb - bt;
    let nettoKering = nettoBasah * (k3 / 100);
    document.getElementById('netto_basah').value = nettoBasah.toFixed(2);
    document.getElementById('netto_kering').value = nettoKering.toFixed(2);
  }

  document.querySelectorAll('.hitungOtomatis').forEach(el => {
    el.addEventListener('input', hitungNetto);
  });

  // === Hitung Stok Akhir ===
  function hitungStok() {
    let awal = parseFloat(document.getElementById('stok_awal').value) || 0;
    let masuk = parseFloat(document.getElementById('masuk_hi').value) || 0;
    let diolah = parseFloat(document.getElementById('diolah').value) || 0;
    let akhir = awal + masuk - diolah;
    document.getElementById('stok_akhir').value = akhir.toFixed(2);
  }

  document.querySelectorAll('.hitungStok').forEach(el => {
    el.addEventListener('input', hitungStok);
  });

  // === Ambil stok awal otomatis berdasarkan bak maturasi ===
  document.getElementById('bak_maturasi').addEventListener('change', function() {
    let bak = this.value;
    if (!bak) return;
    fetch(`/maturasi/stok-terakhir/${encodeURIComponent(bak)}`)
      .then(res => res.json())
      .then(data => {
        document.getElementById('stok_awal').value = data.stok_awal || 0;
        hitungStok();
      })
      .catch(err => console.error('Gagal ambil stok:', err));
  });

  // === Hitung Umur (hari) berdasarkan tanggal ===
  document.getElementById('tanggal').addEventListener('input', function() {
    const today = new Date();
    const tgl = new Date(this.value);
    const diff = Math.round((today - tgl) / (1000 * 60 * 60 * 24));
    document.getElementById('umur').value = isNaN(diff) ? 0 : diff;
  });
});
</script>
