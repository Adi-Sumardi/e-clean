import { useLocalSearchParams } from "expo-router";
import { useAuthStore } from "@/stores/auth-store";
import { PetugasLaporanForm } from "@/components/forms/PetugasLaporanForm";
import { SatpamLaporanForm } from "@/components/forms/SatpamLaporanForm";
import { OfficeBoyLaporanForm } from "@/components/forms/OfficeBoyLaporanForm";
import { PetugasTokoLaporanForm } from "@/components/forms/PetugasTokoLaporanForm";

export default function LaporanScreen() {
  const role = useAuthStore((s) => s.user?.role);
  const params = useLocalSearchParams();
  const lokasiId = params.lokasiId ? Number(params.lokasiId) : undefined;

  switch (role) {
    case "satpam":
      return <SatpamLaporanForm preselectedLokasiId={lokasiId} />;
    case "office_boy":
      return <OfficeBoyLaporanForm preselectedLokasiId={lokasiId} />;
    case "petugas_toko":
      return <PetugasTokoLaporanForm preselectedLokasiId={lokasiId} />;
    case "petugas":
    default:
      return <PetugasLaporanForm preselectedLokasiId={lokasiId} />;
  }
}
