"use client"

import { useEditor, EditorContent } from "@tiptap/react"
import StarterKit from "@tiptap/starter-kit"
import Mention from "@tiptap/extension-mention"
import Link from "@tiptap/extension-link"
import { useState, useEffect, useRef } from "react"
import { Bold, Italic, List, ListOrdered, LinkIcon, AtSign } from "lucide-react"
import { ASSETS_URL } from "../../../utils/helpers"

interface User {
  id: number
  name: string
  email: string
  avatar?: string
}

interface RichTextEditorProps {
  value: string
  onChange: (value: string) => void
  onMentionSelect: (userId: number) => void
  users: User[]
  placeholder?: string
  className?: string
}

export const RichTextEditor = ({
  value,
  onChange,
  onMentionSelect,
  users,
  placeholder = "Type your note here...",
  className = "",
}: RichTextEditorProps) => {
  const [mentionQuery, setMentionQuery] = useState("")
  const [showMentions, setShowMentions] = useState(false)
  const [mentionPosition, setMentionPosition] = useState({ top: 0, left: 0 })
  const [selectedIndex, setSelectedIndex] = useState(0)
  const mentionRef = useRef<HTMLDivElement>(null)

  const filteredUsers = users.filter(
    (user) =>
      user.name.toLowerCase().includes(mentionQuery.toLowerCase()) ||
      user.email.toLowerCase().includes(mentionQuery.toLowerCase()),
  )

  const editor = useEditor({
    extensions: [
      StarterKit,
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: "text-blue-600 underline",
        },
      }),
      Mention.configure({
        HTMLAttributes: {
          class: "mention",
        },
        suggestion: {
          items: ({ query }: { query: string }) => {
            setMentionQuery(query)
            return users
              .filter(
                (user) =>
                  user.name.toLowerCase().includes(query.toLowerCase()) ||
                  user.email.toLowerCase().includes(query.toLowerCase()),
              )
              .slice(0, 10)
          },
          render: () => {
            let popup: HTMLDivElement | null = null

            return {
              onStart: (props: any) => {
                setShowMentions(true)
                setSelectedIndex(0)

                if (!props.clientRect) return

                const rect = props.clientRect()
                setMentionPosition({
                  top: rect.bottom + window.scrollY + 5,
                  left: 0,
                })

                // Create popup element
                popup = document.createElement("div")
                popup.style.position = "absolute"
                popup.style.zIndex = "1000"
                popup.style.top = `${rect.bottom + window.scrollY + 5}px`
                popup.style.left = `${rect.left + window.scrollX}px`
                document.body.appendChild(popup)
              },

              onUpdate: (props: any) => {
                if (!popup || !props.clientRect) return

                const rect = props.clientRect()
                popup.style.top = `${rect.bottom + window.scrollY + 5}px`
                popup.style.left = `${rect.left + window.scrollX}px`
              },

              onKeyDown: (props: any) => {
                const { event } = props

                if (event.key === "ArrowUp") {
                  setSelectedIndex((prev) => (prev > 0 ? prev - 1 : filteredUsers.length - 1))
                  return true
                }

                if (event.key === "ArrowDown") {
                  setSelectedIndex((prev) => (prev < filteredUsers.length - 1 ? prev + 1 : 0))
                  return true
                }

                if (event.key === "Enter" || event.key === "Tab") {
                  const selectedUser = filteredUsers[selectedIndex]
                  if (selectedUser) {
                    props.command({ id: selectedUser.id, label: selectedUser.name })
                    onMentionSelect(selectedUser.id)
                  }
                  return true
                }

                if (event.key === "Escape") {
                  setShowMentions(false)
                  return true
                }

                return false
              },

              onExit: () => {
                setShowMentions(false)
                if (popup) {
                  popup.remove()
                  popup = null
                }
              },
            }
          },
        },
      }),
    ],
    content: value,
    onUpdate: ({ editor }) => {
      onChange(editor.getHTML())
    },
    editorProps: {
      attributes: {
        class: "prose prose-sm max-w-none focus:outline-none min-h-[120px] p-3",
        "data-placeholder": placeholder,
      },
    },
  })

  useEffect(() => {
    if (editor && value !== editor.getHTML()) {
      editor.commands.setContent(value)
    }
  }, [value, editor])

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (mentionRef.current && !mentionRef.current.contains(event.target as Node)) {
        setShowMentions(false)
      }
    }

    if (showMentions) {
      document.addEventListener("mousedown", handleClickOutside)
    }

    return () => {
      document.removeEventListener("mousedown", handleClickOutside)
    }
  }, [showMentions])

  const handleMentionClick = (user: User) => {
    if (editor) {
      editor.chain().focus().insertContent(`${user.name}`).run()
      onMentionSelect(user.id)
      setShowMentions(false)
    }
  }

  if (!editor) {
    return null
  }

  return (
    <div className={`relative border border-gray-200 rounded-lg overflow-hidden ${className}`}>
      {/* Toolbar */}
      <div className="flex items-center space-x-2 p-2 border-b border-gray-200 bg-gray-50">
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBold().run()}
          className={`p-1 rounded hover:bg-gray-200 transition-colors ${editor.isActive("bold") ? "bg-gray-200" : ""}`}
          title="Bold"
        >
          <Bold size={16} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleItalic().run()}
          className={`p-1 rounded hover:bg-gray-200 transition-colors ${
            editor.isActive("italic") ? "bg-gray-200" : ""
          }`}
          title="Italic"
        >
          <Italic size={16} />
        </button>
        <div className="w-px h-4 bg-gray-300" />
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          className={`p-1 rounded hover:bg-gray-200 transition-colors ${
            editor.isActive("bulletList") ? "bg-gray-200" : ""
          }`}
          title="Bullet List"
        >
          <List size={16} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          className={`p-1 rounded hover:bg-gray-200 transition-colors ${
            editor.isActive("orderedList") ? "bg-gray-200" : ""
          }`}
          title="Numbered List"
        >
          <ListOrdered size={16} />
        </button>
        <div className="w-px h-4 bg-gray-300" />
        <button
          type="button"
          onClick={() => {
            const url = prompt("Enter URL:")
            if (url) {
              editor.chain().focus().extendMarkRange("link").setLink({ href: url }).run()
            }
          }}
          className={`p-1 rounded hover:bg-gray-200 transition-colors ${editor.isActive("link") ? "bg-gray-200" : ""}`}
          title="Add Link"
        >
          <LinkIcon size={16} />
        </button>
        <div className="flex items-center text-sm text-gray-500 ml-auto">
          <AtSign size={14} className="mr-1" />
          Type @ to mention users
        </div>
      </div>

      {/* Editor */}
      <EditorContent editor={editor} />

      {/* Custom Mention Dropdown */}
      {showMentions && filteredUsers.length > 0 && (
        <div
          ref={mentionRef}
          className="absolute z-50 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
          style={{
            top: 0,
            left: mentionPosition.left,
          }}
        >
          {filteredUsers.map((user, index) => (
            <button
              key={user.id}
              type="button"
              onClick={() => handleMentionClick(user)}
              className={`w-full text-left px-3 py-2 flex items-center space-x-2 hover:bg-gray-100 transition-colors ${
                index === selectedIndex ? "bg-gray-100" : ""
              }`}
            >
              <div className="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs flex-shrink-0">
                {user.avatar ? (
                  <img
                    src={(ASSETS_URL + '/storage/' + user.avatar) || "/placeholder.svg"}
                    alt={user.name}
                    className="w-full h-full rounded-full object-cover"
                  />
                ) : (
                  user.name.charAt(0).toUpperCase()
                )}
              </div>
              <div className="min-w-0 flex-1">
                <div className="font-medium text-sm truncate">{user.name}</div>
                <div className="text-xs text-gray-500 truncate">{user.email}</div>
              </div>
            </button>
          ))}
        </div>
      )}

      <style>{`
        .ProseMirror {
          outline: none;
        }
        .ProseMirror p.is-editor-empty:first-child::before {
          content: attr(data-placeholder);
          float: left;
          color: #9ca3af;
          pointer-events: none;
          height: 0;
        }
        .mention {
          background-color: #dbeafe;
          color: #1d4ed8;
          padding: 2px 4px;
          border-radius: 4px;
          font-weight: 500;
          white-space: nowrap;
          text-decoration: none;
        }
        .mention:hover {
          background-color: #bfdbfe;
        }
        .ProseMirror p {
          margin: 0.5em 0;
        }
        .ProseMirror p:first-child {
          margin-top: 0;
        }
        .ProseMirror p:last-child {
          margin-bottom: 0;
        }
      `}</style>
    </div>
  )
}
