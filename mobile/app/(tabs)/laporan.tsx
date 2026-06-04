import { useAuthStore } from "@/stores/auth-store";
import { PetugasLaporanForm } from "@/components/forms/PetugasLaporanForm";
import { SatpamLaporanForm } from "@/components/forms/SatpamLaporanForm";
import { OfficeBoyLaporanForm } from "@/components/forms/OfficeBoyLaporanForm";
import { PetugasTokoLaporanForm } from "@/components/forms/PetugasTokoLaporanForm";

export default function LaporanScreen() {
  const role = useAuthStore((s) => s.user?.role);

  switch (role) {
    case "satpam":
      return <SatpamLaporanForm />;
    case "office_boy":
      return <OfficeBoyLaporanForm />;
    case "petugas_toko":
      return <PetugasTokoLaporanForm />;
    case "petugas":
    default:
      return <PetugasLaporanForm />;
  }
}
