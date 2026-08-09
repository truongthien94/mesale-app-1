import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { LoadingState } from "@/components/AsyncState";

export default function Index() {
  const { isLoading, session } = useAuth();
  if (isLoading) return <LoadingState />;
  return <Redirect href={session ? "/home" : "/login"} />;
}
