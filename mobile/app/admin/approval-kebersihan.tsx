import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalKebersihanScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Kebersihan",
        icon: "sparkles-outline",
        color: "#0a7e3e",
        teamLabel: "Petugas Kebersihan",
        scope: "kebersihan",
      }}
    />
  );
}
