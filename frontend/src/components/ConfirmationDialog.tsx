import { useEffect, useRef } from 'react'
import './confirmation_dialog.css'

export type ConfirmationAction = {
  label: string
  onClick: () => void
  variant?: 'primary' | 'secondary' | 'danger'
}

type ConfirmationDialogProps = {
  open: boolean
  title: string
  message: string
  actions: ConfirmationAction[]
  cancelLabel?: string
  onCancel: () => void
}

export default function ConfirmationDialog({
  open,
  title,
  message,
  actions,
  cancelLabel = 'Cancelar',
  onCancel,
}: ConfirmationDialogProps) {
  const dialogRef = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return

    if (open && !dialog.open) dialog.showModal()
    if (!open && dialog.open) dialog.close()
  }, [open])

  return (
    <dialog
      className="sigsa-confirmation-dialog"
      ref={dialogRef}
      aria-labelledby="sigsa-confirmation-title"
      onCancel={(event) => {
        event.preventDefault()
        onCancel()
      }}
    >
      <div className="sigsa-confirmation-content">
        <span className="sigsa-confirmation-eyebrow">CONFIRMACIÓN · SIGSA</span>
        <h2 id="sigsa-confirmation-title">{title}</h2>
        <p>{message}</p>
        <div className="sigsa-confirmation-actions">
          <button className="sigsa-confirmation-button sigsa-confirmation-button-cancel" type="button" onClick={onCancel}>
            {cancelLabel}
          </button>
          {actions.map((action) => (
            <button
              className={`sigsa-confirmation-button sigsa-confirmation-button-${action.variant ?? 'primary'}`}
              key={action.label}
              type="button"
              onClick={action.onClick}
            >
              {action.label}
            </button>
          ))}
        </div>
      </div>
    </dialog>
  )
}
