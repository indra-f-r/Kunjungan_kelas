<?php
defined('INDEX_AUTH') OR die('Direct access not allowed!');

// start admin session
require SB . 'admin/default/session.inc.php';

// privilege check (mengikuti modul Keanggotaan)
$can_read = utility::havePrivilege('membership', 'r');

if (!$can_read) {
    die('<div class="errorBox">Tidak memiliki hak akses</div>');
}

/* ===============================
   PROSES REKAM KEHADIRAN
================================ */

if (isset($_POST['memberID']) && isset($_POST['rombel'])) {

    $rombel = $dbs->escape_string($_POST['rombel']);
    $success = 0;

    foreach ($_POST['memberID'] as $member_id) {

        $member_id = $dbs->escape_string($member_id);

        // ambil nama siswa
        $member_q = $dbs->query("SELECT member_name FROM member WHERE member_id='$member_id'");
        $member_d = $member_q->fetch_assoc();

        if ($member_d) {

            $member_name = $dbs->escape_string($member_d['member_name']);

            // insert kunjungan
            $dbs->query("
                INSERT INTO visitor_count
                (member_id, member_name, institution, room_code, checkin_date)
                VALUES
                ('$member_id','$member_name','$rombel','CLASS',NOW())
            ");

            $success++;
        }
    }

$notif = '<div class="infoBox">✔ '.$success.' kunjungan berhasil direkam</div>';
unset($_GET['rombel']);
}

/* ===============================
   AMBIL DAFTAR ROMBEL
================================ */

$rombel_q = $dbs->query("
SELECT DISTINCT pin
FROM member
WHERE pin IS NOT NULL
AND pin <> ''
ORDER BY pin ASC
");

/* ===============================
   FORM PILIH ROMBEL
================================ */
?>

<div class="menuBox">
<div class="menuBoxInner membershipIcon">
<div class="per_title">
<h2>Kunjungan Kelas</h2>

<div style="display:flex;gap:40px;align-items:flex-start;">
<?php

$rekap = $dbs->query("
SELECT
COUNT(*) as total,
COUNT(DISTINCT member_id) as unik,
MIN(checkin_date) as pertama,
MAX(checkin_date) as terakhir
FROM visitor_count
WHERE DATE(checkin_date)=CURDATE()
");

$r = $rekap->fetch_assoc();

?>

<div style="flex:1;">
<div class="infoBox" style="width:100%;">

<div style="display:flex;justify-content:space-between;flex-wrap:wrap;">

<div style="flex:1;text-align:center;">
<b>Total Kunjungan:</b><br>
<?php echo $r['total']; ?>
</div>

<div style="flex:1;text-align:center;">
<b>Anggota Unik :</b><br>
<?php echo $r['unik']; ?>
</div>

<div style="flex:1;text-align:center;">
<b>Mulai Merekam:</b><br>
<?php echo date('H:i',strtotime($r['pertama'])); ?>
</div>

<div style="flex:1;text-align:center;">
<b>Waktu Terakhir Tercatat :</b><br>
<?php echo date('H:i',strtotime($r['terakhir'])); ?>
</div>

</div>

</div>

</div>

</div>

<div class="sub_section" style="flex:1;">
<?php
if(isset($notif)){
    echo $notif;
}
?>

<form method="get" action="<?= $_SERVER['REQUEST_URI']; ?>">

<label>Pilih Rombel :</label>

<select name="rombel" class="form-control" style="width:250px;display:inline-block;">

<option value="">-- pilih rombel --</option>

<?php
while ($r = $rombel_q->fetch_assoc()) {

    $selected = (isset($_GET['rombel']) && $_GET['rombel'] == $r['pin']) ? 'selected' : '';

    echo "<option value=\"{$r['pin']}\" $selected>{$r['pin']}</option>";
}
?>

</select>

<button type="submit" class="btn btn-primary" style="margin-left:10px;">
Tampilkan Siswa
</button>

</form>

</div>
</div>
</div>
</div>

<?php

/* ===============================
   TAMPILKAN SISWA ROMBEL
================================ */

if (isset($_GET['rombel']) && $_GET['rombel'] != '') {

    $rombel = $dbs->escape_string($_GET['rombel']);

    $siswa_q = $dbs->query("
    SELECT member_id, member_name
    FROM member
    WHERE pin='$rombel'
    ORDER BY member_name
    ");

?>

<div class="menuBox" style="margin-top:0px;">
<div class="menuBoxInner">

<div style="width:100%;padding:0 40px;margin-top:10px;">

<form method="post" action="<?= $_SERVER['REQUEST_URI']; ?>">

<input type="hidden" name="rombel" value="<?php echo $rombel; ?>">

<div style="margin:5px 0;display:flex;gap:5px;align-items:center;">
<button id="pilihBtn" type="button" onclick="pilihSemua()" class="btn btn-secondary" style="margin-left:10px;">
Pilih Semua
</button>

<button id="batalBtn" type="button" onclick="batalPilih()" class="btn btn-warning" disabled style="margin-left:10px;">
Batal Pilih
</button>

<button id="rekamBtn" type="submit" class="btn btn-success" disabled>
Rekam Kehadiran
</button>
</div>

<?php

echo "<div style='column-count:4;column-gap:40px;'>";

while ($s = $siswa_q->fetch_assoc()) {

    echo "<div style='break-inside:avoid;margin-bottom:6px;'>";

    echo "<label>";

    echo "<input type='checkbox' name='memberID[]' value='{$s['member_id']}'> ";

    echo $s['member_name'];

    echo "</label>";

    echo "</div>";
}

echo "</div>";

?>

<br>

</form>

</div>
</div>
</div>

<script>
function toggleAll(source) {
    let checkboxes = document.getElementsByName('memberID[]');

    for(let i=0;i<checkboxes.length;i++){
        checkboxes[i].checked = source.checked;
    }

    updateButton();
}

function pilihSemua(){

    let boxes = document.querySelectorAll("input[name='memberID[]']");

    boxes.forEach(function(box){
        box.checked = true;
    });

    updateButton();
}

function batalPilih(){

    let boxes = document.querySelectorAll("input[name='memberID[]']");

    boxes.forEach(function(box){
        box.checked = false;
    });

    updateButton();
}

function updateButton(){

    let boxes = document.querySelectorAll("input[name='memberID[]']");
    let rekamBtn = document.getElementById("rekamBtn");
    let batalBtn = document.getElementById("batalBtn");

    let count = 0;

    boxes.forEach(function(box){
        if(box.checked){
            count++;
        }
    });

    // tombol rekam
    rekamBtn.disabled = (count === 0);

    // tombol batal pilih
    batalBtn.disabled = (count === 0);
}

document.addEventListener("change", function(e){

    if(e.target.name === "memberID[]"){
        updateButton();
    }

});
</script>

<?php
}
?>
