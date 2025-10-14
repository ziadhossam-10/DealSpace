import React, { ReactNode } from "react";
import { Link } from "react-router-dom";

interface BreadcrumbProps {
  pageTitle: string;
  actionButton?: ReactNode; // Accepts a full button component
}

const PageBreadcrumb: React.FC<BreadcrumbProps> = ({ pageTitle, actionButton }) => {
  return (
    <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
      <h2 className="text-xl font-semibold text-gray-800 dark:text-white/90">
        {pageTitle}
      </h2>
      <nav className="flex items-center gap-3">
          {
            !actionButton && (
              <ol className="flex items-center gap-1.5">
                <li>
                  <Link
                    className="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                    to="/"
                  >
                    Home
                    <svg
                      className="stroke-current"
                      width="17"
                      height="16"
                      viewBox="0 0 17 16"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366"
                        stroke="currentColor"
                        strokeWidth="1.2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                  </Link>
                </li>
                <li className="text-sm text-gray-800 dark:text-white/90">{pageTitle}</li>
              </ol>
            )
          }
        {actionButton && <div>{actionButton}</div>}
      </nav>
    </div>
  );
};

export default PageBreadcrumb;
