import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { LoadingState } from "@/components/AsyncState";

export default function Index() {
  const { isLoading, pendingAuth, session, user } = useAuth();
  if (isLoading) return <LoadingState />;
  return <Redirect href={resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false) ?? "/login"} />;
}
