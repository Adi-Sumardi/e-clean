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
        scope: "ob",
      }}
    />
  );
}
