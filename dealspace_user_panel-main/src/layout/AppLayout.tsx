import React from "react";
import { SidebarProvider } from "../context/SidebarContext";
import { Outlet } from "react-router";
import AppHeader from "./AppHeader";
import Backdrop from "./Backdrop";
import { ToastContainer } from 'react-toastify';

const LayoutContent: React.FC = () => {
  return (
    <div className="min-h-screen xl:flex">
      <div>
        <Backdrop />
      </div>
      <div
        className={`flex-1 transition-all duration-300 ease-in-out`}
      >
        <AppHeader />
        <div>
          <Outlet />
        </div>
      </div>
      <ToastContainer style={{marginTop: 80}}/>
    </div>
  );
};

const AppLayout: React.FC = () => {
  return (
    <SidebarProvider>
      <LayoutContent />
    </SidebarProvider>
  );
};

export default AppLayout;
