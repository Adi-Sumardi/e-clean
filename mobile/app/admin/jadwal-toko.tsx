import { JadwalTimScreen } from "@/components/admin/JadwalTimScreen";

export default function JadwalTokoScreen() {
  return (
    <JadwalTimScreen
      config={{
        title: "Jadwal Petugas Toko",
        icon: "storefront-outline",
        color: "#0891b2",
        noun: "shift",
        shifts: ["Pagi", "Sore"],
        data: [
          {
            id: 1,
            tanggal: "02 Jun",
            shift: "Pagi",
            petugas: "Siti Nurhaliza",
            area: "Toko Utama",
            unit: "Toko",
            status: "completed",
          },
          {
            id: 2,
            tanggal: "02 Jun",
            shift: "Sore",
            petugas: "Mbak Sari",
            area: "Toko Utama",
            unit: "Toko",
            status: "scheduled",
          },
          {
            id: 3,
            tanggal: "03 Jun",
            shift: "Pagi",
            petugas: "Siti Nurhaliza",
            area: "Toko Utama",
            unit: "Toko",
            status: "scheduled",
          },
          {
            id: 4,
            tanggal: "03 Jun",
            shift: "Sore",
            petugas: "Mbak Sari",
            area: "Toko Utama",
            unit: "Toko",
            status: "scheduled",
          },
          {
            id: 5,
            tanggal: "01 Jun",
            shift: "Sore",
            petugas: "Mbak Sari",
            area: "Toko Utama",
            unit: "Toko",
            status: "missed",
          },
        ],
      }}
    />
  );
}
