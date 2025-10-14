"use client"
import { Phone, Mail, Home, Plus, Edit, Trash2 } from "lucide-react"
import { ASSETS_URL, getInitials } from "../../../utils/helpers"

const truncateText = (text: string, maxLength: number) => {
  if (!text) return ""
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + "..."
}

interface ContactInfoProps {
  contact: any
  onEditEmail: (email: any) => void
  onDeleteEmail: (id: number) => void
  onAddEmail: () => void
  onEditPhone: (phone: any) => void
  onDeletePhone: (id: number) => void
  onAddPhone: () => void
  onEditAddress: (address: any) => void
  onDeleteAddress: (id: number) => void
  onAddAddress: () => void
}

export const PersonContactInfo = ({
  contact,
  onEditEmail,
  onDeleteEmail,
  onAddEmail,
  onEditPhone,
  onDeletePhone,
  onAddPhone,
  onEditAddress,
  onDeleteAddress,
  onAddAddress,
}: ContactInfoProps) => {
  return (
    <div className="p-4 border-b">
      <div className="flex items-center space-x-3 mb-2">
        <div className="h-16 w-16 bg-gray-200 text-gray-600 text-xl rounded-full flex items-center justify-center">
          {contact?.picture ? (
            <img
              src={ASSETS_URL + contact.picture || "/placeholder.svg"}
              alt={contact.name}
              className="h-full w-full object-cover rounded-full"
            />
          ) : (
            <span>{getInitials(String(contact?.name))}</span>
          )}
        </div>
        <div>
          <h2 className="text-xl font-medium text-gray-800">{contact?.name}</h2>
          <p className="text-sm text-gray-500">No communication yet</p>
        </div>
      </div>

      <div className="space-y-3 mt-4">
        {/* Phone numbers section */}
        {contact?.phones.map((phone: any) => (
          <div key={phone.id} className="flex items-center justify-between group">
            <div className="flex items-center text-gray-600">
              <Phone size={16} className="mr-2" />
              <span className="text-blue-500">{phone.value}</span>
              <span className="text-gray-400 text-sm ml-1">({phone.type})</span>
              {phone.is_primary && (
                <span className="ml-2 text-xs border border-gray-200 rounded-sm px-1 py-0.5 text-gray-500">
                  Primary
                </span>
              )}
            </div>
            <div className="hidden group-hover:flex items-center space-x-1">
              <button
                onClick={() => onEditPhone(phone)}
                className="p-1 text-gray-400 hover:text-blue-500 rounded-full hover:bg-gray-100"
              >
                <Edit size={14} />
              </button>
              <button
                onClick={() => onDeletePhone(Number(phone?.id))}
                className="p-1 text-gray-400 hover:text-red-500 rounded-full hover:bg-gray-100"
              >
                <Trash2 size={14} />
              </button>
            </div>
          </div>
        ))}
        <button onClick={onAddPhone} className="flex items-center text-blue-500 hover:underline">
          <Plus size={16} className="mr-1" />
          Add phone
        </button>

        {/* Email section */}
        {contact?.emails.map((email: any) => (
          <div key={email.id} className="flex items-center justify-between group">
            <div className="flex items-center text-gray-600">
              <Mail size={16} className="mr-2" />
              <span className="text-blue-500">{email.value}</span>
              <span className="text-gray-400 text-sm ml-1">({email.type})</span>
              {email.is_primary && (
                <span className="ml-2 text-xs border border-gray-200 rounded-sm px-1 py-0.5 text-gray-500">
                  Primary
                </span>
              )}
            </div>
            <div className="hidden group-hover:flex items-center space-x-1">
              <button
                onClick={() => onEditEmail(email)}
                className="p-1 text-gray-400 hover:text-blue-500 rounded-full hover:bg-gray-100"
              >
                <Edit size={14} />
              </button>
              <button
                onClick={() => onDeleteEmail(Number(email?.id))}
                className="p-1 text-gray-400 hover:text-red-500 rounded-full hover:bg-gray-100"
              >
                <Trash2 size={14} />
              </button>
            </div>
          </div>
        ))}
        <button onClick={onAddEmail} className="flex items-center text-blue-500 hover:underline">
          <Plus size={16} className="mr-1" />
          Add email
        </button>

        {/* Address section */}
        {contact?.addresses.map((address: any) => (
          <div key={address.id} className="flex items-center justify-between group">
            <div className="flex items-center text-gray-600">
              <Home size={16} className="mr-2" />
              <div className="flex flex-col max-w-[180px]">
                <span
                  className="text-blue-500 overflow-hidden text-ellipsis whitespace-nowrap"
                  title={address.street_address}
                >
                  {truncateText(address.street_address, 25)}
                </span>
                <span
                  className="text-gray-500 text-sm overflow-hidden text-ellipsis whitespace-nowrap"
                  title={`${address.city}, ${address.state} ${address.postal_code}`}
                >
                  {truncateText(`${address.city}, ${address.state} ${address.postal_code}`, 25)}
                </span>
                <span className="text-gray-400 text-xs">({address.type})</span>
              </div>
            </div>
            <div className="hidden group-hover:flex items-center space-x-1">
              <button
                onClick={() => onEditAddress(address)}
                className="p-1 text-gray-400 hover:text-blue-500 rounded-full hover:bg-gray-100"
              >
                <Edit size={14} />
              </button>
              <button
                onClick={() => onDeleteAddress(Number(address?.id))}
                className="p-1 text-gray-400 hover:text-red-500 rounded-full hover:bg-gray-100"
              >
                <Trash2 size={14} />
              </button>
            </div>
          </div>
        ))}
        {contact?.addresses.length === 0 && (
          <button onClick={onAddAddress} className="flex items-center text-blue-500 hover:underline">
            <Plus size={16} className="mr-1" />
            Add address
          </button>
        )}
      </div>
    </div>
  )
}
