import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalTokoScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Petugas Toko",
        icon: "storefront-outline",
        color: "#0891b2",
        teamLabel: "Petugas Toko",
        data: [
          {
            id: 401,
            tanggal: "02 Jun 2026",
            petugas: "Siti Nurhaliza",
            area: "Toko Utama - Shift Pagi",
            unit: "Toko",
            kegiatan:
              "Laporan harian shift pagi: 47 transaksi, omset Rp 2.4jt, restocking 3 rak",
            dibuat: "30 menit lalu",
          },
          {
            id: 402,
            tanggal: "01 Jun 2026",
            petugas: "Mbak Sari",
            area: "Toko Utama - Shift Sore",
            unit: "Toko",
            kegiatan:
              "Laporan akhir shift: 32 transaksi, omset Rp 1.8jt, tutup kasir",
            dibuat: "Kemarin 22:00",
          },
        ],
      }}
    />
  );
}
