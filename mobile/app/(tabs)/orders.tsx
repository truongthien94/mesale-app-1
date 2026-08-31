import OrdersRoute from "./wallet/orders";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";

export default function OrdersTabRoute() {
  return <IosPayoutRouteGuard><OrdersRoute /></IosPayoutRouteGuard>;
}
