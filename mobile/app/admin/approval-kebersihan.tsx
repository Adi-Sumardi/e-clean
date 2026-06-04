import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalKebersihanScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Kebersihan",
        icon: "sparkles-outline",
        color: "#0a7e3e",
        teamLabel: "Petugas Kebersihan",
        data: [
          {
            id: 101,
            tanggal: "02 Jun 2026",
            petugas: "Rahmat Hidayat",
            area: "Toilet Lt.1 - Gedung A",
            unit: "Office",
            kegiatan: "Pembersihan rutin pagi + mopping + foto bukti sebelum/sesudah",
            dibuat: "2 jam lalu",
          },
          {
            id: 102,
            tanggal: "02 Jun 2026",
            petugas: "Citra Wijaya",
            area: "Pantry Lt.3",
            unit: "Office",
            kegiatan: "Mopping & dusting setelah lunch + cek pasokan tissue",
            dibuat: "30 menit lalu",
          },
          {
            id: 103,
            tanggal: "01 Jun 2026",
            petugas: "Andi Setiawan",
            area: "Lobi Utama",
            unit: "Office",
            kegiatan: "Sweeping + vacuum karpet + lap meja resepsionis",
            dibuat: "Kemarin 16:20",
          },
        ],
      }}
    />
  );
}
