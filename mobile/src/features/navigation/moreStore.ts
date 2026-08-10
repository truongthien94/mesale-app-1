import { create } from "zustand";

type MoreSheetState = {
  isOpen: boolean;
  open: () => void;
  close: () => void;
};

export const useMoreSheetStore = create<MoreSheetState>((set) => ({
  isOpen: false,
  open: () => set({ isOpen: true }),
  close: () => set({ isOpen: false })
}));
