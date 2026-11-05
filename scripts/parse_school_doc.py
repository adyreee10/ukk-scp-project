import sys
import os
import json
import re

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def parse_spreadsheet(file_path, ext=None):
    """
    Membaca seluruh sheet dari file XLSX / XLS menggunakan openpyxl atau xlrd.
    Mengembalikan dictionary { sheet_name: [ [cell, cell, ...], ... ] }
    """
    is_xls = False
    if ext in ['.xls', 'xls']:
        is_xls = True
    elif ext is None and file_path.lower().endswith('.xls'):
        is_xls = True

    if is_xls:
        try:
            import xlrd
            wb = xlrd.open_workbook(file_path)
            sheets_data = {}
            for sheet_name in wb.sheet_names():
                ws = wb.sheet_by_name(sheet_name)
                matrix = []
                for row_idx in range(ws.nrows):
                    row_vals = ws.row_values(row_idx)
                    cleaned_row = []
                    has_data = False
                    for val in row_vals:
                        if val is None or val == '':
                            cleaned_row.append("")
                        else:
                            s_val = str(val).strip()
                            if s_val.endswith('.0') and isinstance(val, (int, float)):
                                s_val = s_val[:-2]
                            if s_val != "":
                                has_data = True
                            cleaned_row.append(s_val)
                    if has_data:
                        while cleaned_row and cleaned_row[-1] == "":
                            cleaned_row.pop()
                        matrix.append(cleaned_row)
                sheets_data[sheet_name] = matrix

            return {
                "status": "success",
                "type": "spreadsheet",
                "sheet_names": wb.sheet_names(),
                "sheets": sheets_data
            }
        except Exception as e_xls:
            # Jika gagal xlrd, coba openpyxl sebagai fallback
            pass

    import openpyxl
    target_path = file_path
    temp_copy = None

    if not file_path.lower().endswith(('.xlsx', '.xlsm', '.xltx')):
        import tempfile, shutil
        fd, temp_copy = tempfile.mkstemp(suffix='.xlsx')
        os.close(fd)
        shutil.copyfile(file_path, temp_copy)
        target_path = temp_copy

    try:
        wb = openpyxl.load_workbook(target_path, data_only=True)
        sheets_data = {}

        for sheet_name in wb.sheetnames:
            ws = wb[sheet_name]
            matrix = []
            for row in ws.iter_rows(values_only=True):
                # Normalisasi baris menjadi array string
                cleaned_row = []
                has_data = False
                for val in row:
                    if val is None:
                        cleaned_row.append("")
                    else:
                        s_val = str(val).strip()
                        if s_val != "":
                            has_data = True
                        cleaned_row.append(s_val)
                if has_data:
                    # Pangkas trailing empty cells
                    while cleaned_row and cleaned_row[-1] == "":
                        cleaned_row.pop()
                    matrix.append(cleaned_row)

            sheets_data[sheet_name] = matrix
    finally:
        if temp_copy and os.path.exists(temp_copy):
            try:
                os.unlink(temp_copy)
            except Exception:
                pass

    return {
        "status": "success",
        "type": "spreadsheet",
        "sheet_names": wb.sheetnames,
        "sheets": sheets_data
    }

def normalize_class_name(raw_name):
    """
    Normalisasi nama kelas seperti 'X RPL1' -> 'X RPL 1'
    """
    if not raw_name:
        return ""
    name = re.sub(r'[\r\n\t]+', ' ', str(raw_name)).strip()
    name = re.sub(r'\s*[:=]\s*', '', name)
    name = re.sub(r'\s+', ' ', name)
    # Ubah format seperti 'X RPL1' atau 'XI TKJ2' menjadi 'X RPL 1'
    m = re.match(r'^(X|XI|XII)\s*([A-Za-z]+)\s*(\d*)$', name, re.IGNORECASE)
    if m:
        grade = m.group(1).upper()
        major = m.group(2).upper()
        num = m.group(3)
        return f"{grade} {major} {num}".strip()
    return name

def parse_pdf(file_path):
    """
    Membaca dokumen PDF menggunakan pdfplumber.
    Mendukung Timetable FET (Jadwal Kelas, Jadwal Guru, Jadwal Ruang) dan Dokumen Daftar Siswa/Guru/Ruang.
    """
    import pdfplumber

    pages_data = []
    extracted_schedules = []
    extracted_teachers = {}
    extracted_rooms = {}
    extracted_classes = set()
    doc_category = "general"

    days_list = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"]

    with pdfplumber.open(file_path) as pdf:
        total_pages = len(pdf.pages)

        # 1. Deteksi cepat kategori dokumen dari halaman pertama
        first_page_text = pdf.pages[0].extract_text() or ""
        if "JADWAL RUANG" in first_page_text.upper() or "SMK NEGERI 1 BANTUL" in first_page_text:
            if "JADWAL RUANG" in first_page_text.upper():
                doc_category = "timetable_room"
            elif any(re.search(r'Kelas\s+X', first_page_text, re.I) for _ in [1]):
                doc_category = "timetable_class"
            elif any(re.search(r'"\d+[\.,]', first_page_text) for _ in [1]):
                doc_category = "timetable_teacher"

        for idx, page in enumerate(pdf.pages):
            raw_text = page.extract_text() or ""
            text = page.extract_text(layout=True) or raw_text

            # --- A. EKSTRAKSI LEGENDA RUANGAN (Halaman Matriks Ruang / Footer) ---
            room_legend_matches = re.findall(r'(L\.\s*[A-Za-z0-9]+|L\.\s*IPA|L\.\s*PB|OR\d*|HALL)\s*:\s*([^:\n\r]+?)(?=(?:L\.\s*[A-Za-z0-9]+|L\.\s*IPA|L\.\s*PB|OR\d*|HALL)\s*:|$|\n)', text, re.IGNORECASE)
            for rm_code, rm_name in room_legend_matches:
                clean_code = re.sub(r'\s+', '', rm_code.upper())
                clean_name = re.sub(r'\s+', ' ', rm_name).strip()
                clean_name = re.sub(r'(TTD|Kepala Sekolah|Bantul,.*|NIP\s*\d+|RAHARJO.*).*', '', clean_name, flags=re.I).strip(" ,.;-")
                if len(clean_name) >= 3 and len(clean_name) <= 80:
                    extracted_rooms[clean_code] = clean_name

            # Ekstraksi Ruangan Kelas Teori (Ruang 1 s.d Ruang 34) jika matriks ruang
            if "JADWAL RUANG" in text.upper() or "KETERANGAN :" in text.upper():
                for cell_num in re.findall(r'\b([1-9]|[12][0-9]|3[0-4])\b', text):
                    r_code = f"Ruang {cell_num}"
                    if r_code not in extracted_rooms:
                        extracted_rooms[r_code] = r_code

            # --- B. EKSTRAKSI GURU DARI DAFTAR ISI FET (Pages 1 & 2) ---
            is_daftar_isi = ("daftar isi" in raw_text.lower()) or (idx == 1 and doc_category == "timetable_teacher")
            if is_daftar_isi:
                for line in raw_text.splitlines():
                    clean_line = line.strip('\"“\'” ')
                    m = re.match(r'^(\d{1,3})[.,\s]*(.+)$', clean_line)
                    if m:
                        t_code = m.group(1)
                        clean_t_name = m.group(2).strip('\"“\'” ,.;-')
                        if len(clean_t_name) >= 3 and not re.match(r'^(DAFTAR|HALAMAN|SENIN|SELASA|RABU|KAMIS|JUMAT|SMK|KELAS|FET|NAMA|KOMENTAR)', clean_t_name, re.I):
                            if t_code in extracted_teachers and extracted_teachers[t_code] != clean_t_name:
                                extracted_teachers[f"{t_code}_bis"] = clean_t_name
                            else:
                                extracted_teachers[t_code] = clean_t_name
                if doc_category == "timetable_teacher" and idx >= 1:
                    # Seluruh 91 guru sudah lengkap diekstrak dari Halaman 1 & 2
                    break
                continue

            # Jika dokumen adalah jadwal per guru (90+ halaman) dan sudah selesai membaca Daftar Isi, lewati extract_tables yang sangat berat
            tables = []
            if doc_category != "timetable_teacher" or total_pages <= 10:
                tables = page.extract_tables() or []

            clean_tables = []
            for t in tables:
                clean_t = []
                for row in t:
                    clean_row = [str(c).strip() if c is not None else "" for c in row]
                    if any(clean_row):
                        clean_t.append(clean_row)
                if clean_t:
                    clean_tables.append(clean_t)

            pages_data.append({
                "page_number": idx + 1,
                "text": text,
                "tables": clean_tables
            })

            # Header fallback dari teks halaman
            class_header_match = re.search(r'(?:SMK NEGERI 1 BANTUL\s+)?([X|XI|XII]+\s+[A-Za-z0-9\s]+)', text[:200], re.I)
            fallback_class = normalize_class_name(class_header_match.group(1)) if class_header_match else None
            if fallback_class:
                extracted_classes.add(fallback_class)

            teacher_header_match = re.search(r'["“](\d{1,3})[\.,\s]+([^"”\n\r]+)["”]?', text[:200])
            fallback_teacher_code = teacher_header_match.group(1) if teacher_header_match else None
            fallback_teacher_name = re.sub(r'\s+', ' ', teacher_header_match.group(2)).strip().strip('"” ,.;-') if teacher_header_match else None
            if fallback_teacher_code and fallback_teacher_name:
                extracted_teachers[fallback_teacher_code] = fallback_teacher_name

            # Proses setiap tabel FET pada halaman ini
            for table in clean_tables:
                if len(table) < 2:
                    continue

                # Cari baris hari (Senin, Selasa, dst) di 4 baris pertama
                day_row_idx = -1
                day_col_map = {}
                for r_idx in range(min(4, len(table))):
                    candidate_row = [str(c).lower() if c else '' for c in table[r_idx]]
                    temp_map = {}
                    for col_i, col_txt in enumerate(candidate_row):
                        for d in days_list:
                            if d.lower() == col_txt or f" {d.lower()} " in f" {col_txt} ":
                                temp_map[col_i] = d
                                break
                    if len(temp_map) >= 3:
                        day_row_idx = r_idx
                        day_col_map = temp_map
                        break

                if day_row_idx == -1 or not day_col_map:
                    continue

                # Baris judul halaman berada sebelum baris hari (misal baris 0)
                current_page_class = fallback_class
                current_page_teacher_code = fallback_teacher_code
                current_page_teacher_name = fallback_teacher_name

                if day_row_idx > 0:
                    title_text = ' '.join([str(c) for c in table[day_row_idx - 1] if c]).strip()
                    tm = re.search(r'["“]?(\d{1,3})[\.,\s]+([^"”\n\r]+)["”]?', title_text)
                    cm = re.search(r'\b(X|XI|XII)\s*([A-Za-z]+)\s*(\d*)\b', title_text, re.I)

                    if tm and not cm:
                        current_page_teacher_code = tm.group(1).strip()
                        current_page_teacher_name = re.sub(r'\s+', ' ', tm.group(2)).strip().strip('"” ,.;-')
                        extracted_teachers[current_page_teacher_code] = current_page_teacher_name
                        current_page_class = None
                    elif cm:
                        current_page_class = normalize_class_name(cm.group(0))
                        extracted_classes.add(current_page_class)
                        current_page_teacher_code = None
                        current_page_teacher_name = None

                # Baris setelah header hari adalah baris jadwal
                for r_idx in range(day_row_idx + 1, len(table)):
                    row = table[r_idx]
                    if not row:
                        continue

                    time_raw = str(row[0] or '').strip()
                    if not time_raw or 'jadwal dihasilkan' in time_raw.lower() or 'kembali ke' in time_raw.lower():
                        continue

                    time_m = re.findall(r'(\d{1,2}[:.]\d{2})', time_raw)
                    start_time = time_m[0].replace('.', ':') if time_m else "07:15"
                    end_time = time_m[1].replace('.', ':') if len(time_m) > 1 else None

                    if len(start_time) == 4 and start_time[1] == ':':
                        start_time = '0' + start_time
                    if len(start_time) == 5:
                        start_time = start_time + ':00'

                    if end_time:
                        if len(end_time) == 4 and end_time[1] == ':':
                            end_time = '0' + end_time
                        if len(end_time) == 5:
                            end_time = end_time + ':00'

                    # Periksa setiap kolom hari
                    for col_i, day_name in day_col_map.items():
                        if col_i >= len(row):
                            continue
                        cell_content = row[col_i].strip()
                        if not cell_content or cell_content in ['---', '-x-', '-']:
                            continue

                        # --- KASUS 1: TABEL JADWAL KELAS ---
                        if current_page_class:
                            m = re.search(r'["“](\d{1,3})[\.,\s]+(.*)', cell_content, re.DOTALL)
                            if m:
                                t_code = m.group(1).strip()
                                rest = m.group(2)
                                room_m = re.search(r'\b(LAB\s*\d+|LAB\s*IPA|LAB\s*PB|OR\d*|HALL|L\.\s*\d+|L\.\s*IPA|L\.\s*PB)\b', rest, re.I)
                                room = room_m.group(1).strip() if room_m else 'Ruang Teori'
                                t_name = rest[:room_m.start()] if room_m else rest
                                t_name = re.sub(r'\s+', ' ', t_name).strip('"” \t\r\n,.;-')
                                subject = cell_content[:m.start()]
                                subject = re.sub(r'\s+', ' ', subject).strip()

                                extracted_schedules.append({
                                    'class': current_page_class,
                                    'day': day_name,
                                    'start_time': start_time,
                                    'end_time': end_time,
                                    'subject': subject or 'Mata Pelajaran',
                                    'teacher_code': t_code,
                                    'teacher_name': t_name,
                                    'room': room
                                })
                                extracted_teachers[t_code] = t_name

                        # --- KASUS 2: TABEL JADWAL GURU ---
                        # Sel berisi: "X TKJ2 \n Bahasa Indonesia \n LAB 11"
                        elif current_page_teacher_code and current_page_teacher_name:
                            lines = [l.strip() for l in cell_content.splitlines() if l.strip()]
                            if lines:
                                # Baris pertama adalah kelas
                                c_cand = normalize_class_name(lines[0])
                                subject = ' '.join(lines[1:]) if len(lines) > 1 else 'Mata Pelajaran'
                                
                                # Cek apakah ada ruang di baris terakhir
                                room = 'Ruang Teori'
                                if len(lines) > 2:
                                    rm = re.search(r'\b(LAB\s*\d+|LAB\s*IPA|LAB\s*PB|OR\d*|HALL|L\.\s*\d+|L\.\s*IPA|L\.\s*PB|R\.\s*\d+)\b', lines[-1], re.I)
                                    if rm:
                                        room = rm.group(1).strip()
                                        subject = ' '.join(lines[1:-1])

                                extracted_schedules.append({
                                    'class': c_cand,
                                    'day': day_name,
                                    'start_time': start_time,
                                    'end_time': end_time,
                                    'subject': subject.strip() or 'Mata Pelajaran Umum',
                                    'teacher_code': current_page_teacher_code,
                                    'teacher_name': current_page_teacher_name,
                                    'room': room
                                })
                                extracted_classes.add(c_cand)

    return {
        "status": "success",
        "type": "pdf",
        "doc_category": doc_category,
        "total_pages": len(pages_data),
        "pages": pages_data,
        "schedules": extracted_schedules,
        "teachers": [{"code": k, "name": v} for k, v in extracted_teachers.items()],
        "rooms": [{"code": k, "name": v} for k, v in extracted_rooms.items()],
        "classes": list(extracted_classes)
    }

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "Argumen path file tidak ditemukan."}))
        sys.exit(1)

    file_path = sys.argv[1]
    forced_ext = sys.argv[2] if len(sys.argv) > 2 else ""

    if not os.path.exists(file_path):
        print(json.dumps({"status": "error", "message": f"File '{file_path}' tidak ditemukan."}))
        sys.exit(1)

    ext = forced_ext.lower().strip() if forced_ext else os.path.splitext(file_path)[1].lower()
    if ext and not ext.startswith('.'):
        ext = '.' + ext

    # Cek magic bytes jika ekstensi .tmp atau tidak ada ekstensi
    if ext in ['.tmp', '']:
        try:
            with open(file_path, 'rb') as f:
                magic = f.read(8)
                if magic.startswith(b'PK\x03\x04'):
                    ext = '.xlsx'
                elif magic.startswith(b'%PDF'):
                    ext = '.pdf'
                elif magic.startswith(b'\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1'): # OLE2 CFBF for .xls
                    ext = '.xls'
        except Exception:
            pass

    try:
        if ext in ['.xlsx', '.xlsm', '.xltx', '.xls']:
            result = parse_spreadsheet(file_path, ext)
        elif ext == '.pdf':
            result = parse_pdf(file_path)
        else:
            print(json.dumps({"status": "error", "message": f"Ekstensi file '{ext}' tidak didukung oleh parser python."}))
            sys.exit(1)

        print(json.dumps(result, ensure_ascii=True))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}, ensure_ascii=True))
        sys.exit(1)

if __name__ == '__main__':
    main()
