import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalOBScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Office Boy",
        icon: "cafe-outline",
        color: "#7e5a17",
        teamLabel: "Office Boy",
        scope: "ob",
      }}
    />
  );
}
