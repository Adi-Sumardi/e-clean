import { JadwalTimScreen } from "@/components/admin/JadwalTimScreen";

export default function JadwalSatpamScreen() {
  return (
    <JadwalTimScreen
      config={{
        title: "Jadwal Patroli Satpam",
        icon: "shield-outline",
        color: "#005bbf",
        noun: "patroli",
        shifts: ["Pagi", "Siang", "Malam"],
        data: [
          {
            id: 1,
            tanggal: "02 Jun",
            shift: "Pagi",
            petugas: "Pak Joko",
            area: "Pos 1 - Gate Utama",
            unit: "Office",
            status: "completed",
          },
          {
            id: 2,
            tanggal: "02 Jun",
            shift: "Pagi",
            petugas: "Pak Hendro",
            area: "Pos 2 - Parkir Belakang",
            unit: "Office",
            status: "completed",
          },
          {
            id: 3,
            tanggal: "02 Jun",
            shift: "Siang",
            petugas: "Pak Joko",
            area: "Pos 3 - Lt.2 Gedung A",
            unit: "Office",
            status: "scheduled",
          },
          {
            id: 4,
            tanggal: "02 Jun",
            shift: "Siang",
            petugas: "Pak Bambang",
            area: "Pos 4 - Gudang Logistik",
            unit: "Office",
            status: "scheduled",
          },
          {
            id: 5,
            tanggal: "02 Jun",
            shift: "Malam",
            petugas: "Pak Hendro",
            area: "Pos 5 - Perimeter",
            unit: "Office",
            status: "scheduled",
          },
          {
            id: 6,
            tanggal: "01 Jun",
            shift: "Malam",
            petugas: "Pak Bambang",
            area: "Pos 1 - Gate Utama",
            unit: "Office",
            status: "missed",
          },
        ],
      }}
    />
  );
}
