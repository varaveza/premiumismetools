import os
import random
from faker import Faker
from PIL import Image, ImageDraw, ImageFont
import glob
import argparse

# Konfigurasi
BASE_TEMPLATE = "base kosong.png"
FONT_PATH = "fonts/Poppins-Regular.ttf"
FONT_BOLD_PATH = "fonts/Poppins-Bold.ttf"
OUTPUT_DIR = "output"
PHOTO_DIR = "fotosiswa"

fake = Faker('en_IN')  # Gunakan lokal India

# Posisi teks sesuai koordinat yang diberikan, dikurangi 15 pixel agar naik ke atas
POS = {
    'name': (271, 309 - 15),
    'id': (271, 346 - 11),
    'dob': (271, 392 - 15),
    'address1': (271, 440 - 15),
    'address2': (271, 470 - 15),  # Baris kedua address
    'year': (950, 600),
}

# Area foto berdasarkan 4 titik koordinat
PHOTO_TOP_LEFT = (710, 185)
PHOTO_TOP_RIGHT = (950, 185)
PHOTO_BOTTOM_LEFT = (706, 512)
PHOTO_BOTTOM_RIGHT = (950, 512)

# Hitung ukuran dan posisi foto
PHOTO_X = min(PHOTO_TOP_LEFT[0], PHOTO_BOTTOM_LEFT[0])
PHOTO_Y = PHOTO_TOP_LEFT[1]
PHOTO_WIDTH = max(PHOTO_TOP_RIGHT[0], PHOTO_BOTTOM_RIGHT[0]) - PHOTO_X
PHOTO_HEIGHT = PHOTO_BOTTOM_LEFT[1] - PHOTO_TOP_LEFT[1]

# Fungsi crop dan resize foto agar pas di frame
def crop_and_resize(IMG, size):
    w, h = IMG.size
    tw, th = size
    aspect = w / h
    target_aspect = tw / th
    if aspect > target_aspect:
        # Crop kiri-kanan
        new_w = int(h * target_aspect)
        left = (w - new_w) // 2
        IMG = IMG.crop((left, 0, left + new_w, h))
    else:
        # Crop atas-bawah
        new_h = int(w / target_aspect)
        top = (h - new_h) // 2
        IMG = IMG.crop((0, top, w, top + new_h))
    return IMG.resize((tw, th), Image.LANCZOS)

def get_all_photos():
    """Mendapatkan semua foto dari folder fotosiswa"""
    files = glob.glob(os.path.join(PHOTO_DIR, "*"))
    files = [f for f in files if f.lower().endswith(('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tiff'))]
    return files

def generate_student_data():
    # Daftar nama sederhana yang lebih banyak
    first_names = [
        "Budi", "Siti", "Ahmad", "Dewi", "Joko", "Rina", "Agus", "Maya", "Rudi", "Nina",
        "Andi", "Tina", "Dian", "Eka", "Fajar", "Gita", "Hana", "Indra", "Jaya", "Kiki",
        "Lina", "Mira", "Nando", "Oki", "Putra", "Qori", "Rama", "Salsa", "Tono", "Uli",
        "Vina", "Wawan", "Xena", "Yani", "Zaki", "Bagus", "Cici", "Dodi", "Evi", "Fani",
        "Gilang", "Hesti", "Irwan", "Jihan", "Kamal", "Lusi", "Mega", "Novi", "Omar", "Prita"
    ]
    last_names = [
        "Santoso", "Wati", "Hidayat", "Sari", "Widodo", "Putri", "Prasetyo", "Indah", "Kurniawan", "Lestari",
        "Saputra", "Permata", "Wijaya", "Utami", "Rahma", "Syah", "Anggraini", "Saputri", "Gunawan", "Setiawan",
        "Maulana", "Fauzi", "Ramadhan", "Suryani", "Anjani", "Pramudita", "Handayani", "Suryono", "Yuliani", "Arifin",
        "Sasmita", "Utomo", "Sukma", "Wibowo", "Suharto", "Sukardi", "Suryadi", "Suhendra", "Suhartini", "Suharto",
        "Suhartono", "Suharyanto", "Suharyono", "Suhendra", "Suhirman", "Suhud", "Sukamto", "Sukardi", "Sukarto", "Sukirno"
    ]
    
    # Pilih nama secara acak dan bisa bolak-balik
    if random.choice([True, False]):
        # Format: First Name Last Name
        name = f"{random.choice(first_names)} {random.choice(last_names)}"
    else:
        # Format: Last Name First Name (bolak-balik)
        name = f"{random.choice(last_names)} {random.choice(first_names)}"
    
    name = name.upper()
    id_ = f"{random.randint(1000,9999)}-{random.randint(1000,9999)}"
    dob = fake.date_of_birth(minimum_age=18, maximum_age=25).strftime("%d/%m/%Y")
    address1 = "NEW DELHI, INDIA"
    address2 = "11001"
    return name, id_, dob, address1, address2

def create_card(photo_path):
    """Membuat kartu dengan foto yang diberikan"""
    # Buka base template
    card = Image.open(BASE_TEMPLATE).convert("RGBA")
    draw = ImageDraw.Draw(card)

    # Font
    font_label = ImageFont.truetype(FONT_PATH, 32)
    font_bold = ImageFont.truetype(FONT_BOLD_PATH if os.path.exists(FONT_BOLD_PATH) else FONT_PATH, 32)
    font_year = ImageFont.truetype(FONT_BOLD_PATH if os.path.exists(FONT_BOLD_PATH) else FONT_PATH, 36)

    # Data siswa
    name, id_, dob, address1, address2 = generate_student_data()

    # Tulis data (hanya isi, tanpa label karena sudah ada di template)
    draw.text(POS['name'], name, font=font_label, fill="#1a365d")
    draw.text(POS['id'], id_, font=font_label, fill="#1a365d")
    draw.text(POS['dob'], dob, font=font_label, fill="#1a365d")
    draw.text(POS['address1'], address1, font=font_label, fill="#1a365d")
    draw.text(POS['address2'], address2, font=font_label, fill="#1a365d")

    # Tahun akademik
    # Foto siswa
    if photo_path and os.path.exists(photo_path):
        try:
            IMG = Image.open(photo_path).convert("RGB")
            IMG = crop_and_resize(IMG, (PHOTO_WIDTH, PHOTO_HEIGHT))
            # --- Masking rounded rectangle dan outline hitam ---
            mask = Image.new("L", (PHOTO_WIDTH, PHOTO_HEIGHT), 0)
            draw_mask = ImageDraw.Draw(mask)
            corner_radius = min(30, PHOTO_WIDTH // 4, PHOTO_HEIGHT // 4)  # agar tidak terlalu besar
            draw_mask.rounded_rectangle(
                [(0, 0), (PHOTO_WIDTH, PHOTO_HEIGHT)],
                radius=corner_radius, fill=255
            )
            # Buat foto dengan sudut membulat
            IMG_rounded = Image.new("RGBA", (PHOTO_WIDTH, PHOTO_HEIGHT))
            IMG_rounded.paste(IMG, (0, 0), mask=mask)
            # Outline hitam
            outline = Image.new("RGBA", (PHOTO_WIDTH, PHOTO_HEIGHT))
            draw_outline = ImageDraw.Draw(outline)
            draw_outline.rounded_rectangle(
                [(2, 2), (PHOTO_WIDTH-2, PHOTO_HEIGHT-2)],
                radius=corner_radius, outline="black", width=4
            )
            IMG_rounded = Image.alpha_composite(IMG_rounded, outline)
            # Tempelkan ke kartu
            card.paste(IMG_rounded, (PHOTO_X, PHOTO_Y), mask=IMG_rounded)
        except Exception as e:
            print(f"Warning: error processing photo {photo_path}: {e}")
            # Placeholder jika ada error
            ph = Image.new("RGB", (PHOTO_WIDTH, PHOTO_HEIGHT), "#eee")
            card.paste(ph, (PHOTO_X, PHOTO_Y))
    else:
        # Placeholder jika tidak ada foto
        ph = Image.new("RGB", (PHOTO_WIDTH, PHOTO_HEIGHT), "#eee")
        card.paste(ph, (PHOTO_X, PHOTO_Y))

    # Simpan hasil
    if not os.path.exists(OUTPUT_DIR):
        os.makedirs(OUTPUT_DIR)
    
    # Format nama file: IMG_{rand 1001-9999}
    randnum = random.randint(1001, 9999)
    filename = f"IMG_{randnum}.png"
    output_path = os.path.join(OUTPUT_DIR, filename)
    card.save(output_path)
    print(f"Saved: {output_path}")
    return output_path

def process_all_photos(delete_source=False):
    """Memproses semua foto di folder fotosiswa"""
    photos = get_all_photos()
    
    if not photos:
        print("No photos found in folder fotosiswa/")
        return
    
    print(f"Found {len(photos)} photos to process")
    
    processed_count = 0
    for i, photo_path in enumerate(photos, 1):
        print(f"\nProcessing photo {i}/{len(photos)}: {os.path.basename(photo_path)}")
        
        try:
            # Buat kartu dengan foto ini
            output_path = create_card(photo_path)
            
            # Hapus foto setelah digunakan (opsional)
            if delete_source:
                try:
                    os.remove(photo_path)
                    print(f"Deleted source: {photo_path}")
                except Exception as e:
                    print(f"Warning: failed to delete {photo_path}: {e}")
            
            processed_count += 1
            
        except Exception as e:
            print(f"Error processing {photo_path}: {e}")
            continue
    
    print(f"\nDone. Successfully processed {processed_count} of {len(photos)} photos")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Generate student card(s)")
    parser.add_argument("--photo", dest="photo", type=str, default=None, help="Path to a specific photo to generate a single card for")
    parser.add_argument("--all", dest="process_all", action="store_true", help="Proses semua foto di folder fotosiswa")
    parser.add_argument("--delete-source", dest="delete_source", action="store_true", help="Hapus foto sumber setelah diproses (default: tidak)")
    args = parser.parse_args()

    if args.photo:
        if os.path.exists(args.photo):
            out = create_card(args.photo)
            print(out)
        else:
            print(f"Photo not found: {args.photo}")
    elif args.process_all:
        process_all_photos(delete_source=args.delete_source)
    else:
        print("No action. Use --photo <path> (called by PHP) or --all for bulk process.")