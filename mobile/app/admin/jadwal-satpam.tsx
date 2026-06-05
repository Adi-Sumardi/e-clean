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
        scope: "satpam",
      }}
    />
  );
}
