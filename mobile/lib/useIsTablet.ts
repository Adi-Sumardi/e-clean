import { useWindowDimensions } from "react-native";

export const TABLET_BREAKPOINT = 768;

export function useIsTablet() {
  const { width } = useWindowDimensions();
  return width >= TABLET_BREAKPOINT;
}

export function useDeviceSize() {
  const { width, height } = useWindowDimensions();
  const isTablet = width >= TABLET_BREAKPOINT;
  const isLandscape = width > height;
  return { width, height, isTablet, isLandscape };
}
