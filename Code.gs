/**
 * GOOGLE APPS SCRIPT - DATABASE ENGINE APLIKASI ABSENSI GURU (DENGAN REPAIR TOOL)
 * 
 * CARA PENGGUNAAN:
 * 1. Buka Google Sheets Anda.
 * 2. Klik Extensions -> Apps Script.
 * 3. Hapus kode lama dan paste seluruh isi file ini.
 * 4. Klik ikon Save (Ctrl+S).
 * 
 * CARA MERAPIKAN DATABASE YANG SUDAH BERANTAKAN:
 * - Pada menu dropdown fungsi di Apps Script (samping tombol Run), pilih fungsi: "fixAndCleanAbsensiSheet".
 * - Klik tombol "Run" (Jalankan).
 * - Fungsi ini akan otomatis membaca data lama Anda, menyusun ulang kolom-kolom 
 *   (G: Mata Pelajaran, H: Guru, I: Materi Pembelajaran, J: Catatan Kelas, K: Detail Kehadiran),
 *   dan meletakkan data ke kolom yang tepat secara otomatis!
 * 
 * DEPLOY ULANG:
 * 5. Klik Deploy -> New Deployment.
 * 6. Pilih tipe Web App.
 * 7. Konfigurasi:
 *    - Description: Aplikasi Absensi v1.3 (Database Repair Tool)
 *    - Execute as: Me
 *    - Who has access: Anyone
 * 8. Klik Deploy. Salin URL Web App yang baru dan update nilai GAS_API_URL di file .env Anda.
 */

// Fungsi untuk Merapikan & Menyusun Ulang Kolom Absensi Secara Otomatis
function fixAndCleanAbsensiSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName("Absensi");
  if (!sheet) return "Sheet Absensi tidak ditemukan.";
  
  var lastRow = sheet.getLastRow();
  var lastCol = sheet.getLastColumn();
  
  var targetHeaders = ["ID_Absen", "Tanggal", "Semester", "Kelas", "Jam_Mulai", "Jam_Selesai", "Mata_Pelajaran", "Guru", "Materi_Pembelajaran", "Catatan_Kelas", "Detail_Kehadiran"];
  
  if (lastRow <= 1) {
    // Jika sheet kosong atau hanya header, langsung setel ulang headernya
    sheet.clear();
    sheet.getRange(1, 1, 1, targetHeaders.length).setValues([targetHeaders]);
    return "Header Absensi berhasil disetel ulang (karena data kosong).";
  }
  
  var range = sheet.getDataRange();
  var values = range.getValues();
  var currentHeaders = values[0];
  
  // Ekstrak data baris ke dalam objek key-value berdasarkan nama header saat ini
  var rows = [];
  for (var i = 1; i < values.length; i++) {
    var row = {};
    for (var j = 0; j < currentHeaders.length; j++) {
      var headerName = currentHeaders[j];
      if (headerName) {
        row[headerName] = values[i][j];
      }
    }
    rows.push(row);
  }
  
  // Bersihkan seluruh sheet
  sheet.clear();
  
  // Tulis ulang header yang benar sesuai susunan yang diminta
  sheet.getRange(1, 1, 1, targetHeaders.length).setValues([targetHeaders]);
  
  // Susun kembali nilai sel berdasarkan header tujuan
  var outputValues = [];
  rows.forEach(function(row) {
    var rowValues = [];
    targetHeaders.forEach(function(header) {
      var val = row[header] !== undefined ? row[header] : "";
      rowValues.push(val);
    });
    outputValues.push(rowValues);
  });
  
  if (outputValues.length > 0) {
    sheet.getRange(2, 1, outputValues.length, targetHeaders.length).setValues(outputValues);
  }
  
  // Format Tanggal kembali agar rapi di spreadsheet (Format: YYYY-MM-DD)
  var colTanggalIndex = targetHeaders.indexOf("Tanggal") + 1;
  sheet.getRange(2, colTanggalIndex, outputValues.length, 1).setNumberFormat("yyyy-mm-dd");
  
  return "Database Absensi berhasil dirapikan! Semua data dipindahkan ke kolom yang tepat.";
}

// Inisialisasi Database (Membuat sheet jika belum ada)
function initializeDatabase() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  
  getOrCreateSheet(ss, "Data_Siswa", ["ID", "Kelas", "NIS", "Nama Siswa"]);
  getOrCreateSheet(ss, "Absensi", ["ID_Absen", "Tanggal", "Semester", "Kelas", "Jam_Mulai", "Jam_Selesai", "Mata_Pelajaran", "Guru", "Materi_Pembelajaran", "Catatan_Kelas", "Detail_Kehadiran"]);
  getOrCreateSheet(ss, "Nilai", ["ID_Nilai", "NIS", "Kelas", "Semester", "Tugas_1", "Tugas_2", "Tugas_3", "PTS", "PAS", "Praktik"]);
  getOrCreateSheet(ss, "Mapel", ["ID_Mapel", "Mata_Pelajaran"]);
  getOrCreateSheet(ss, "user", ["ID", "Nama", "Email", "NIP", "Mata_Pelajaran"]);
}

function getOrCreateSheet(ss, name, headers) {
  var sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
    sheet.appendRow(headers);
  }
  return sheet;
}

// Membaca baris dari sheet dan mengonversi menjadi Array of Objects
function getSheetRows(sheetName) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(sheetName);
  if (!sheet) return [];
  
  var range = sheet.getDataRange();
  var values = range.getValues();
  if (values.length <= 1) return [];
  
  var headers = values[0];
  var list = [];
  for (var i = 1; i < values.length; i++) {
    var item = {};
    for (var j = 0; j < headers.length; j++) {
      item[headers[j]] = values[i][j];
    }
    item["_rowNum"] = i + 1;
    list.push(item);
  }
  return list;
}

// Helper untuk menulis data baris berdasarkan nama header secara dinamis
function writeRowData(sheet, headers, rowNum, rowData) {
  Object.keys(rowData).forEach(function(key) {
    var colIndex = headers.indexOf(key) + 1;
    if (colIndex > 0) {
      sheet.getRange(rowNum, colIndex).setValue(rowData[key]);
    }
  });
}

// Helper untuk menambah baris baru dengan memetakan kolom secara dinamis
function appendRowData(sheet, headers, rowData) {
  var nextRow = sheet.getLastRow() + 1;
  writeRowData(sheet, headers, nextRow, rowData);
}

// Menghitung Statistik untuk Dashboard
function calculateStats(siswa, absensi, nilai) {
  var totalSiswa = siswa.length;
  
  // Ambil daftar kelas unik
  var kelasSet = {};
  siswa.forEach(function(s) {
    if (s["Kelas"]) {
      kelasSet[s["Kelas"]] = true;
    }
  });
  var classes = Object.keys(kelasSet);
  var totalKelas = classes.length;
  
  // Hitung persentase kehadiran per kelas dan global
  var kelasAttendance = {};
  classes.forEach(function(k) {
    kelasAttendance[k] = { totalSlots: 0, totalHadir: 0 };
  });
  
  var totalHadirGlobal = 0;
  var totalSlotsGlobal = 0;
  
  absensi.forEach(function(row) {
    var kelas = row["Kelas"];
    var detailStr = row["Detail_Kehadiran"];
    if (kelas && detailStr) {
      try {
        var detail = JSON.parse(detailStr);
        if (!kelasAttendance[kelas]) {
          kelasAttendance[kelas] = { totalSlots: 0, totalHadir: 0 };
        }
        Object.keys(detail).forEach(function(nis) {
          var val = detail[nis];
          var status = (typeof val === 'object' && val !== null) ? (val.status || "Hadir") : val;
          kelasAttendance[kelas].totalSlots++;
          totalSlotsGlobal++;
          if (status === "Hadir" || status === "H") {
            kelasAttendance[kelas].totalHadir++;
            totalHadirGlobal++;
          }
        });
      } catch(e) {}
    }
  });
  
  var kehadiranKelas = [];
  classes.forEach(function(k) {
    var ka = kelasAttendance[k];
    var rate = ka.totalSlots > 0 ? Math.round((ka.totalHadir / ka.totalSlots) * 100) : 0;
    kehadiranKelas.push({ "kelas": k, "rate": rate });
  });
  kehadiranKelas.sort(function(a, b) { return b.rate - a.rate; });
  
  var globalAttendanceRate = totalSlotsGlobal > 0 ? Math.round((totalHadirGlobal / totalSlotsGlobal) * 100) : 0;
  
  // Hitung rata-rata nilai akademik per kelas
  var kelasGrades = {};
  classes.forEach(function(k) {
    kelasGrades[k] = { totalScores: 0, sumScores: 0 };
  });
  
  var sumAvgGlobal = 0;
  var countStudentsGlobal = 0;
  
  nilai.forEach(function(row) {
    var nis = row["NIS"];
    var kelas = row["Kelas"];
    if (nis && kelas) {
      var scores = [];
      var keys = ["Tugas_1", "Tugas_2", "Tugas_3", "PTS", "PAS", "Praktik"];
      keys.forEach(function(k) {
        var val = row[k];
        if (val !== "" && val !== null && !isNaN(val)) {
          scores.push(Number(val));
        }
      });
      if (scores.length > 0) {
        var avg = scores.reduce(function(a, b) { return a + b; }, 0) / scores.length;
        if (!kelasGrades[kelas]) {
          kelasGrades[kelas] = { totalScores: 0, sumScores: 0 };
        }
        kelasGrades[kelas].totalScores++;
        kelasGrades[kelas].sumScores += avg;
        
        sumAvgGlobal += avg;
        countStudentsGlobal++;
      }
    }
  });
  
  var nilaiKelas = [];
  classes.forEach(function(k) {
    var kg = kelasGrades[k];
    var avg = kg.totalScores > 0 ? Math.round((kg.sumScores / kg.totalScores) * 10) / 10 : 0;
    nilaiKelas.push({ "kelas": k, "avg": avg });
  });
  nilaiKelas.sort(function(a, b) { return b.avg - a.avg; });
  
  var globalGradesAvg = countStudentsGlobal > 0 ? (Math.round((sumAvgGlobal / countStudentsGlobal) * 10) / 10).toFixed(1) : "0.0";
  
  return {
    "totalSiswa": totalSiswa,
    "totalKelas": totalKelas,
    "globalAttendanceRate": globalAttendanceRate,
    "globalGradesAvg": globalGradesAvg,
    "kehadiranKelas": kehadiranKelas,
    "nilaiKelas": nilaiKelas
  };
}

// Mengambil seluruh data awal (GET getInitialData)
function getInitialData() {
  initializeDatabase();
  
  var siswa = getSheetRows("Data_Siswa");
  var absensi = getSheetRows("Absensi");
  var nilai = getSheetRows("Nilai");
  var mapel = getSheetRows("Mapel");
  var user = getSheetRows("user");
  
  // Format sheet user agar sesuai dengan struktur Guru yang diharapkan oleh aplikasi
  var guru = [];
  user.forEach(function(u) {
    if (u["Nama"]) {
      guru.push({
        "ID_Guru": u["ID"],
        "Nama_Guru": u["Nama"]
      });
    }
  });
  
  absensi.forEach(function(row) {
    if (row["Tanggal"] instanceof Date) {
      row["Tanggal"] = Utilities.formatDate(row["Tanggal"], Session.getScriptTimeZone(), "yyyy-MM-dd");
    }
  });
  
  var stats = calculateStats(siswa, absensi, nilai);
  
  var config = {
    "SEMESTER_LIST": ["Ganjil", "Genap"],
    "DEFAULT_SEMESTER": "Ganjil"
  };
  
  return {
    "success": true,
    "data": {
      "siswa": siswa,
      "absensi": absensi,
      "nilai": nilai,
      "guru": guru,
      "mapel": mapel,
      "user": user,
      "stats": stats,
      "config": config
    }
  };
}

// ─────────────────────────────────────────────────────────────────────────────
// POST ACTIONS HANDLERS
// ─────────────────────────────────────────────────────────────────────────────

function addStudent(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Data_Siswa", ["ID", "Kelas", "NIS", "Nama Siswa"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  var rows = getSheetRows("Data_Siswa");
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["NIS"]) === String(data.nis)) {
      return { "success": false, "message": "NIS sudah terdaftar." };
    }
  }
  
  var id = "S-" + Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyyMMddHHmmss") + Math.floor(Math.random() * 1000);
  
  var rowData = {
    "ID": id,
    "Kelas": data.kelas,
    "NIS": data.nis,
    "Nama Siswa": data.nama
  };
  appendRowData(sheet, headers, rowData);
  
  return { "success": true, "message": "Siswa " + data.nama + " berhasil ditambahkan." };
}

function importStudents(rows) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Data_Siswa", ["ID", "Kelas", "NIS", "Nama Siswa"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  var existingRows = getSheetRows("Data_Siswa");
  var existingNis = {};
  existingRows.forEach(function(r) {
    existingNis[String(r["NIS"])] = true;
  });
  
  var count = 0;
  var timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyyMMddHHmmss");
  
  rows.forEach(function(row, idx) {
    var nis = String(row.nis);
    if (!existingNis[nis]) {
      var id = "S-" + timestamp + idx;
      var rowData = {
        "ID": id,
        "Kelas": row.kelas,
        "NIS": row.nis,
        "Nama Siswa": row.nama
      };
      appendRowData(sheet, headers, rowData);
      existingNis[nis] = true;
      count++;
    }
  });
  
  return { "success": true, "message": count + " siswa berhasil diimpor." };
}

function editStudent(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Data_Siswa", ["ID", "Kelas", "NIS", "Nama Siswa"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var rows = getSheetRows("Data_Siswa");
  
  var rowNum = -1;
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["NIS"]) === String(data.old_nis)) {
      rowNum = rows[i]["_rowNum"];
      break;
    }
  }
  
  if (rowNum === -1) {
    return { "success": false, "message": "Siswa tidak ditemukan." };
  }
  
  var rowData = {
    "Kelas": data.kelas,
    "NIS": data.nis,
    "Nama Siswa": data.nama
  };
  writeRowData(sheet, headers, rowNum, rowData);
  
  var nilaiSheet = ss.getSheetByName("Nilai");
  if (nilaiSheet) {
    var nilaiHeaders = nilaiSheet.getRange(1, 1, 1, nilaiSheet.getLastColumn()).getValues()[0];
    var nilaiRows = getSheetRows("Nilai");
    nilaiRows.forEach(function(nr) {
      if (String(nr["NIS"]) === String(data.old_nis)) {
        writeRowData(nilaiSheet, nilaiHeaders, nr["_rowNum"], {
          "NIS": data.nis,
          "Kelas": data.kelas
        });
      }
    });
  }
  
  return { "success": true, "message": "Siswa " + data.nama + " berhasil diperbarui." };
}

function deleteStudent(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Data_Siswa", ["ID", "Kelas", "NIS", "Nama Siswa"]);
  var rows = getSheetRows("Data_Siswa");
  
  var rowNum = -1;
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["NIS"]) === String(data.nis)) {
      rowNum = rows[i]["_rowNum"];
      break;
    }
  }
  
  if (rowNum === -1) {
    return { "success": false, "message": "Siswa tidak ditemukan." };
  }
  
  sheet.deleteRow(rowNum);
  
  var nilaiSheet = ss.getSheetByName("Nilai");
  if (nilaiSheet) {
    var nilaiRows = getSheetRows("Nilai");
    for (var j = nilaiRows.length - 1; j >= 0; j--) {
      if (String(nilaiRows[j]["NIS"]) === String(data.nis)) {
        nilaiSheet.deleteRow(nilaiRows[j]["_rowNum"]);
      }
    }
  }
  
  return { "success": true, "message": "Siswa berhasil dihapus." };
}



// ── MANAJEMEN PENGGUNA TERVERIFIKASI ─────────────────────────────────────────

function addUser(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "user", ["ID", "Nama", "Email", "NIP", "Mata_Pelajaran"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  var rows = getSheetRows("user");
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["Email"]).trim().toLowerCase() === String(data.email).trim().toLowerCase()) {
      return { "success": false, "message": "Email sudah terdaftar." };
    }
  }
  
  var rowData = {
    "ID": data.id,
    "Nama": data.name,
    "Email": data.email,
    "NIP": data.nip || "",
    "Mata_Pelajaran": data.subjects || ""
  };
  appendRowData(sheet, headers, rowData);
  


  return { "success": true, "message": "Data pengguna berhasil ditambahkan ke spreadsheet." };
}

// ── MANAJEMEN MATA PELAJARAN ──────────────────────────────────────────────────

function addSubject(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Mapel", ["ID_Mapel", "Mata_Pelajaran"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  var rows = getSheetRows("Mapel");
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["Mata_Pelajaran"]).trim().toLowerCase() === String(data.name).trim().toLowerCase()) {
      return { "success": false, "message": "Mata pelajaran sudah terdaftar." };
    }
  }
  
  var id = "M-" + Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyyMMddHHmmss") + Math.floor(Math.random() * 1000);
  
  var rowData = {
    "ID_Mapel": id,
    "Mata_Pelajaran": data.name
  };
  appendRowData(sheet, headers, rowData);
  
  return { "success": true, "message": "Mata pelajaran " + data.name + " berhasil ditambahkan." };
}

function editSubject(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Mapel", ["ID_Mapel", "Mata_Pelajaran"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var rows = getSheetRows("Mapel");
  
  var rowNum = -1;
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["ID_Mapel"]) === String(data.id)) {
      rowNum = rows[i]["_rowNum"];
      break;
    }
  }
  
  if (rowNum === -1) {
    return { "success": false, "message": "Mata pelajaran tidak ditemukan." };
  }
  
  writeRowData(sheet, headers, rowNum, { "Mata_Pelajaran": data.name });
  
  return { "success": true, "message": "Mata pelajaran berhasil diperbarui." };
}

function deleteSubject(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Mapel", ["ID_Mapel", "Mata_Pelajaran"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var rows = getSheetRows("Mapel");
  
  var rowNum = -1;
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i]["ID_Mapel"]) === String(data.id)) {
      rowNum = rows[i]["_rowNum"];
      break;
    }
  }
  
  if (rowNum === -1) {
    return { "success": false, "message": "Mata pelajaran tidak ditemukan." };
  }
  
  sheet.deleteRow(rowNum);
  return { "success": true, "message": "Mata pelajaran berhasil dihapus." };
}

// ── ABSENSI ──────────────────────────────────────────────────────────────────

function saveAttendance(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Absensi", ["ID_Absen", "Tanggal", "Semester", "Kelas", "Jam_Mulai", "Jam_Selesai", "Mata_Pelajaran", "Guru", "Materi_Pembelajaran", "Catatan_Kelas", "Detail_Kehadiran"]);
  
  // Baca susunan header saat ini secara dinamis
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  
  var detailStr = JSON.stringify(data.detail);
  var id = data.id || data.id_absen || data.ID_Absen;
  
  var dateVal = new Date(data.tanggal);
  
  var rowData = {
    "Tanggal": dateVal,
    "Semester": data.semester,
    "Kelas": data.kelas,
    "Jam_Mulai": data.jamMulai,
    "Jam_Selesai": data.jamSelesai,
    "Mata_Pelajaran": data.mataPelajaran,
    "Guru": data.guru,
    "Materi_Pembelajaran": data.materi,
    "Catatan_Kelas": data.catatan,
    "Detail_Kehadiran": detailStr
  };
  
  if (id) {
    var rows = getSheetRows("Absensi");
    var rowNum = -1;
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i]["ID_Absen"]) === String(id)) {
        rowNum = rows[i]["_rowNum"];
        break;
      }
    }
    
    if (rowNum !== -1) {
      writeRowData(sheet, headers, rowNum, rowData);
      return { "success": true, "message": "Absensi kelas " + data.kelas + " berhasil diperbarui." };
    }
  }
  
  var newId = "A-" + Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyyMMddHHmmss") + Math.floor(Math.random() * 1000);
  rowData["ID_Absen"] = newId;
  
  appendRowData(sheet, headers, rowData);
  
  return { "success": true, "message": "Absensi kelas " + data.kelas + " berhasil disimpan." };
}

// ── PENILAIAN ────────────────────────────────────────────────────────────────

function saveGrades(data) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = getOrCreateSheet(ss, "Nilai", ["ID_Nilai", "NIS", "Kelas", "Semester", "Tugas_1", "Tugas_2", "Tugas_3", "PTS", "PAS", "Praktik"]);
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var rows = getSheetRows("Nilai");
  
  var type = data.type; 
  var grades = data.grades; 
  
  var colIndex = headers.indexOf(type) + 1;
  
  if (colIndex <= 4) {
    return { "success": false, "message": "Jenis penilaian tidak valid." };
  }
  
  var count = 0;
  var timestamp = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyyMMddHHmmss");
  
  Object.keys(grades).forEach(function(nis) {
    var score = grades[nis] === "" ? "" : Number(grades[nis]);
    
    var rowNum = -1;
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i]["NIS"]) === String(nis) && 
          String(rows[i]["Semester"]) === String(data.semester) && 
          String(rows[i]["Kelas"]) === String(data.kelas)) {
        rowNum = rows[i]["_rowNum"];
        break;
      }
    }
    
    if (rowNum !== -1) {
      sheet.getRange(rowNum, colIndex).setValue(score);
    } else {
      var newId = "N-" + timestamp + count;
      var rowData = {
        "ID_Nilai": newId,
        "NIS": nis,
        "Kelas": data.kelas,
        "Semester": data.semester
      };
      rowData[type] = score;
      appendRowData(sheet, headers, rowData);
      
      rows.push({
        "ID_Nilai": newId,
        "NIS": nis,
        "Kelas": data.kelas,
        "Semester": data.semester,
        "_rowNum": sheet.getLastRow()
      });
    }
    count++;
  });
  
  return { "success": true, "message": "Nilai " + type.replace("_", " ") + " berhasil disimpan." };
}

// ─────────────────────────────────────────────────────────────────────────────
// GET & POST API GATEWAY
// ─────────────────────────────────────────────────────────────────────────────

function doGet(e) {
  var action = e.parameter.action;
  var result;
  
  try {
    if (action === "getInitialData") {
      result = getInitialData();
    } else if (action === "getDashboardStats") {
      var data = getInitialData();
      result = { "success": true, "stats": data.data.stats };
    } else {
      result = { "success": false, "message": "Aksi GET tidak dikenal: " + action };
    }
  } catch(err) {
    result = { "success": false, "message": "Error: " + err.toString() };
  }
  
  return ContentService.createTextOutput(JSON.stringify(result))
    .setMimeType(ContentService.MimeType.JSON);
}

function doPost(e) {
  var result;
  try {
    var requestData = JSON.parse(e.postData.contents);
    var action = requestData.action;
    var data = requestData.data;
    
    if (action === "addStudent") {
      result = addStudent(data);
    } else if (action === "importStudents") {
      result = importStudents(data);
    } else if (action === "editStudent") {
      result = editStudent(data);
    } else if (action === "deleteStudent") {
      result = deleteStudent(data);

    } else if (action === "addSubject") {
      result = addSubject(data);
    } else if (action === "editSubject") {
      result = editSubject(data);
    } else if (action === "deleteSubject") {
      result = deleteSubject(data);
    } else if (action === "saveAttendance") {
      result = saveAttendance(data);
    } else if (action === "saveGrades") {
      result = saveGrades(data);
    } else if (action === "addUser") {
      result = addUser(data);
    } else {
      result = { "success": false, "message": "Aksi POST tidak dikenal: " + action };
    }
  } catch(err) {
    result = { "success": false, "message": "Error: " + err.toString() };
  }
  
  return ContentService.createTextOutput(JSON.stringify(result))
    .setMimeType(ContentService.MimeType.JSON);
}
