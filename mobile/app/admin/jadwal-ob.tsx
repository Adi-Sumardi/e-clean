import { JadwalTimScreen } from "@/components/admin/JadwalTimScreen";

export default function JadwalOBScreen() {
  return (
    <JadwalTimScreen
      config={{
        title: "Jadwal Office Boy",
        icon: "cafe-outline",
        color: "#7e5a17",
        noun: "tugas",
        shifts: ["Pagi", "Siang"],
        data: [
          {
            id: 1,
            tanggal: "02 Jun",
            shift: "Pagi",
            petugas: "Rahmat OB",
            area: "Pantry Lantai 1",
            unit: "Office",
            status: "completed",
          },
          {
            id: 2,
            tanggal: "02 Jun",
            shift: "Pagi",
            petugas: "Andi OB",
            area: "Lobi Utama",
            unit: "Office",
            status: "completed",
          },
          {
            id: 3,
            tanggal: "02 Jun",
            shift: "Pagi",
            petugas: "Rahmat OB",
            area: "Ruang Rapat Direksi",
            unit: "Office",
            status: "scheduled",
          },
          {
            id: 4,
            tanggal: "02 Jun",
            shift: "Siang",
            petugas: "Andi OB",
            area: "Pantry Lantai 2",
            unit: "Office",
            status: "scheduled",
          },
          {
            id: 5,
            tanggal: "02 Jun",
            shift: "Siang",
            petugas: "Dede OB",
            area: "Ruang Rapat Kecil",
            unit: "Office",
            status: "scheduled",
          },
          {
            id: 6,
            tanggal: "01 Jun",
            shift: "Siang",
            petugas: "Dede OB",
            area: "Toilet Pria Lt.1",
            unit: "Office",
            status: "missed",
          },
        ],
      }}
    />
  );
}
