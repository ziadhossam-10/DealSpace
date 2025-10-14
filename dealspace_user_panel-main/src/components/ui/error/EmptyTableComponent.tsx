import Button from "../button/Button";

type EmptyTableProps = {
    handleShow: () => void;
    text?: string;
};

export const EmptyTableComponent: React.FC<EmptyTableProps> = ({ handleShow, text }) => {
    return (
        <section className="rounded-xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
            <div className="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 lg:px-6">
                <div className="mx-auto max-w-screen-sm text-center">
                    <h1 className="mb-4 text-7xl tracking-tight font-extrabold lg:text-9xl text-red-600 dark:text-red-500 w-full flex justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" width="60" height="60" strokeWidth="2">
                            <path d="M12 9h.01"></path>
                            <path d="M11 12h1v4h1"></path>
                            <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z"></path>
                        </svg>
                    </h1>
                    <p className={`mb-4 text-3xl tracking-tight font-bold text-gray-900 md:text-4xl dark:text-white ${text && "mb-6"}`}>
                        {text || "No data found!"}
                    </p>
                    {
                        text && (
                            <Button size="sm" onClick={handleShow} startIcon={(
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" width="18" height="18" strokeWidth="1.5">
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                    <path d="M9 12h6"></path>
                                    <path d="M12 9v6"></path>
                                </svg>
                            )}>
                                {text}
                            </Button>
                        )
                    }
                </div>
            </div>
        </section>
    );
};
