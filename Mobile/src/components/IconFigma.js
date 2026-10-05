import * as Lucide from 'lucide-react-native'
import { Platform } from 'react-native'

// Giữ tên icon mà các màn hiện có đang gọi khi đổi sang bộ Lucide của bản xuất.
const tenCu = {
  'bar-chart-2': 'ChartNoAxesColumn',
  'check-circle': 'CircleCheck',
  'check-circle-2': 'CircleCheck',
  'edit-3': 'Pencil',
  'help-circle': 'CircleHelp',
  'alert-circle': 'CircleAlert',
  'alert-triangle': 'TriangleAlert',
  grid: 'Grid2X2',
  layout: 'PanelsTopLeft',
  'more-horizontal': 'Ellipsis',
  'more-vertical': 'EllipsisVertical',
  'x-circle': 'CircleX',
}

export default function IconFigma({
  ten,
  size = 22,
  color,
  accessible,
  ...props
}) {
  const tenLucide =
    tenCu[ten] || ten.replace(/(^|[-_])(\w)/g, (_, __, c) => c.toUpperCase())
  const Icon = Lucide[tenLucide]
  if (!Icon) throw new Error(`Chưa ánh xạ icon Lucide: ${ten}`)
  return (
    <Icon
      size={size}
      color={color}
      strokeWidth={2}
      {...(Platform.OS === 'web'
        ? { 'aria-hidden': true }
        : { accessible: false })}
      {...props}
    />
  )
}
