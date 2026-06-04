import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalSatpamScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Patroli Satpam",
        icon: "shield-checkmark-outline",
        color: "#005bbf",
        teamLabel: "Satpam",
        data: [
          {
            id: 201,
            tanggal: "02 Jun 2026",
            petugas: "Pak Hendro",
            area: "Pos 2 - Parkir Belakang",
            unit: "Office",
            kegiatan:
              "Patroli sore + cek kondisi pos + foto perimeter aman",
            dibuat: "1 jam lalu",
          },
          {
            id: 202,
            tanggal: "01 Jun 2026",
            petugas: "Pak Joko",
            area: "Pos 5 - Perimeter",
            unit: "Office",
            kegiatan:
              "Patroli malam keliling area + temuan: lampu mati di sisi timur",
            dibuat: "Kemarin 22:30",
          },
        ],
      }}
    />
  );
}
