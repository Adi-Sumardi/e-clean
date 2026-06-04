import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalOBScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Office Boy",
        icon: "cafe-outline",
        color: "#7e5a17",
        teamLabel: "Office Boy",
        data: [
          {
            id: 301,
            tanggal: "02 Jun 2026",
            petugas: "Rahmat OB",
            area: "Ruang Rapat Direksi",
            unit: "Office",
            kegiatan:
              "Setup rapat direksi + serve refreshment 8 orang + clear up",
            dibuat: "1 jam lalu",
          },
          {
            id: 302,
            tanggal: "02 Jun 2026",
            petugas: "Andi OB",
            area: "Pantry Lantai 2",
            unit: "Office",
            kegiatan: "Refill kopi, teh, gula + cek stok air mineral",
            dibuat: "45 menit lalu",
          },
          {
            id: 303,
            tanggal: "01 Jun 2026",
            petugas: "Dede OB",
            area: "Lobi Utama",
            unit: "Office",
            kegiatan: "Antar dokumen tanda tangan dari Finance ke Direksi",
            dibuat: "Kemarin 14:00",
          },
        ],
      }}
    />
  );
}
