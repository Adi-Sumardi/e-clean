import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalSatpamScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Patroli Satpam",
        icon: "shield-checkmark-outline",
        color: "#005bbf",
        teamLabel: "Satpam",
        scope: "satpam",
      }}
    />
  );
}
