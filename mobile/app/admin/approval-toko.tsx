import { ApprovalTimScreen } from "@/components/admin/ApprovalTimScreen";

export default function ApprovalTokoScreen() {
  return (
    <ApprovalTimScreen
      config={{
        title: "Approval Petugas Toko",
        icon: "storefront-outline",
        color: "#0891b2",
        teamLabel: "Petugas Toko",
        scope: "toko",
      }}
    />
  );
}
