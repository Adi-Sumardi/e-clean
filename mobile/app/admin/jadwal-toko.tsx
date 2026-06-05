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
        scope: "toko",
      }}
    />
  );
}
