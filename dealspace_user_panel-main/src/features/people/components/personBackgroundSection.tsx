"use client"

const truncateText = (text: string, maxLength: number) => {
  if (!text) return ""
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + "..."
}

interface PersonBackgroundSectionProps {
  background?: string
  onEditBackground: () => void
}

export const PersonBackgroundSection = ({ background, onEditBackground }: PersonBackgroundSectionProps) => {
  return (
    <div className="p-4 border-b">
      <h3 className="text-xs font-semibold text-gray-500 mb-2">BACKGROUND</h3>
      <div>
        {background ? (
          <div className="mt-2">
            <p className="text-gray-700 max-w-[300px]">{truncateText(background, 100)}</p>
            <button onClick={onEditBackground} className="text-blue-500 p-0 h-auto hover:underline mt-1">
              Edit background
            </button>
          </div>
        ) : (
          <button onClick={onEditBackground} className="text-blue-500 p-0 h-auto hover:underline">
            Add background
          </button>
        )}
      </div>
    </div>
  )
}
