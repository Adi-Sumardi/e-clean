import { useAuthStore } from "@/stores/auth-store";
import { SatpamDashboard } from "@/components/dashboards/SatpamDashboard";
import { SuperAdminDashboard } from "@/components/dashboards/SuperAdminDashboard";
import { SupervisorDashboard } from "@/components/dashboards/SupervisorDashboard";
import { PetugasDashboard } from "@/components/dashboards/PetugasDashboard";
import { OfficeBoyDashboard } from "@/components/dashboards/OfficeBoyDashboard";
import { PetugasTokoDashboard } from "@/components/dashboards/PetugasTokoDashboard";

export default function DashboardIndex() {
  const role = useAuthStore((s) => s.user?.role);

  switch (role) {
    case "super_admin":
      return <SuperAdminDashboard />;
    case "supervisor":
      return <SupervisorDashboard />;
    case "petugas":
      return <PetugasDashboard />;
    case "office_boy":
      return <OfficeBoyDashboard />;
    case "petugas_toko":
      return <PetugasTokoDashboard />;
    case "satpam":
    default:
      return <SatpamDashboard />;
  }
}
